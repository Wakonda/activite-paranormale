<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Video;
use App\Entity\Contact;
use App\Entity\Theme;
use App\Entity\Language;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;

class VideoController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em)
    {
		$locale = $request->getLocale();

		$entities = $em->getRepository(Video::class)->getAllVideoByThemeAndLanguage($locale);
		$nbr = $em->getRepository(Video::class)->nbrVideo($locale);

		$datas = [];

		foreach($entities as $entity)
			$datas[$entity["parentTheme"]][] = $entity;

		return $this->render('video/Video/index.html.twig', [
			"datas" => $datas,
			'nbr' => $nbr
		]);
    }
	
	public function tabVideoAction($id, $theme)
	{
		return $this->render('video/Video/tabVideo.html.twig', [
			'themeDisplay' => $theme,
			'themeId' => $id
		]);	
	}

	public function tabVideoDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $themeId)
	{
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];
		$language = $request->getLocale();

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Video::class)->getTabVideo($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Video::class)->getTabVideo($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$thumbnail = $entity->getThumbnailVideo();
			$src = null;
			
			if(!empty($thumbnail) and empty($entity->getPhoto())) {
				$src = $thumbnail;
			} else {
				$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
				$src = $request->getBasePath().'/'.$photo[2];
			}
			$row = [];
			$row[] = '<img src="'.$src.'" alt="" style="max-width: 150px">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function readAction(Request $request, EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Video::class)->find($id);

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(Video::class)->getPreviousAndNextEntities($entity, $request->getLocale());

		if($entity->getPlatform() == "Youtube" and is_null($entity->getMediaVideo()))
		{
			if(!$this->isYoutubeVideoExists($entity->getEmbeddedCode()))
				$entity->setAvailable(false);
			else
				$entity->setAvailable(true);
			
			$em->persist($entity);
			$em->flush();
		}

		$params = new \stdClass();
		$params->captcha = null;

        $form = $this->createFormBuilder($params)
            ->add('captcha',\Symfony\Component\Form\Extension\Core\Type\TextType::class, array('required' => true, 'constraints' => array(new \Symfony\Component\Validator\Constraints\NotBlank())))
            ->getForm();

		return $this->render('video/Video/readVideo.html.twig', [
			'previousAndNextEntities' => $previousAndNextEntities,
			'entity' => $entity,
			'form' => $form->createView()
		]);
	}
	
	public function notifyDeletedVideoAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, MailerInterface $mailer, $id)
	{
		$video = $em->getRepository(Video::class)->find($id);
		
		$params = new \stdClass();
		$params->captcha = null;

        $form = $this->createFormBuilder($params)
            ->add('captcha',\Symfony\Component\Form\Extension\Core\Type\TextType::class, array('required' => true, 'constraints' => array(new \Symfony\Component\Validator\Constraints\NotBlank())))
            ->getForm();
		$form->handleRequest($request);

        $response = new Response();
        $response->setStatusCode(400);

		if($form->get("captcha")->getData() != $request->getSession()->get("captcha_word"))
			$form->get('captcha')->addError(new \Symfony\Component\Form\FormError($translator->trans('captcha.error.InvalidCaptcha', [], 'validators')));

		if($form->isValid() and $form->isSubmitted()) {
			$entity  = new Contact();
			$entity->setDateContact(new \DateTime("now"));
			$entity->setStateContact(0);
			$entity->setPseudoContact($this->getClientIp($request));
			$entity->setMessageContact("Avertissement : Vidéo potentiellement supprimée => <a href='".$this->generateUrl('Video_Read', ["id" => $video->getId(), "title_slug" => $video->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL)."'>".$video->getTitle()."</a>");
			$entity->setEmailContact($_ENV["MAILER_CONTACT"]);
			$entity->setSubjectContact("Suppression d'une vidéo");

			$email = (new Email())
				->from($_ENV["MAILER_CONTACT"])
				->to($_ENV["MAILER_CONTACT"])
				->subject("Suppression d'une vidéo")
				->html($this->renderView('contact/Contact/mail.html.twig', ['entity' => $entity]));

			$mailer->send($email);

			$em->persist($entity);
			$em->flush();
			$response->setStatusCode(200);
		}

		$response->setContent($this->render('video/Video/_notify_video.html.twig', [
			'entity' => $video,
			'form' => $form->createView()
		])->getContent());

		return $response;
	}

	public function countVideoAction(EntityManagerInterface $em, $lang)
	{
		$nbrTabVideo = $em->getRepository(Video::class)->nbrVideo($lang);
		return new Response($nbrTabVideo);
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(EntityManagerInterface $em, APHtml2Pdf $html2pdf, $id)
	{
		$entity = $em->getRepository(Video::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The video does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("video/Video/pdfVersion.html.twig", ["entity" => $entity]);

		return $html2pdf->generatePdf($content->getContent());
	}
	
	public function embeddedAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Video::class)->find($id);
		
        return $this->render('video/Video/embedded.html.twig', [
            'entity' => $entity
        ]);
	}

	// INDEX
	public function sliderAction(Request $request, EntityManagerInterface $em)
	{
		$entity = $em->getRepository(Video::class)->getSliderNew($request->getLocale());

		return $this->render("video/Video/slider.html.twig", [
			"entity" => $entity
		]);
	}

	// Check if video exists
	private function isYoutubeVideoExists($videoFrame)
	{
		$dom = new \DOMDocument();
		$dom->loadHTML($videoFrame);
		
		$iframeNode = $dom->getElementsByTagName("iframe");
		$src = $iframeNode->item(0)->getAttribute("src");
		
		if(is_null($src))
			return false;
		
		$url = parse_url($src);		
		$pathArray = explode('/', $url['path']);
		$id = end($pathArray);

		if(!$id)
			return false;
		else
		{
			$videoUrl = "https://www.youtube.com/watch?v=".$id;
			$headers = get_headers("https://www.youtube.com/oembed?url=".$videoUrl."&format=json");

			if(strstr($headers[0], "200"))
				return true;
		}

		return false;
	}
	
	private function getClientIp(Request $request)
	{
		$ip = "UNKNOWN IP";
		$server = $request->server;
		
		if ($server->has('HTTP_CLIENT_IP')) {
			$ip = $server->get('HTTP_CLIENT_IP');
		} elseif ($server->has('HTTP_X_FORWARDED_FOR')) {
			$ip = $server->get('HTTP_X_FORWARDED_FOR');
		} else {
			$ip = $server->get('REMOTE_ADDR');
		}
		
		return $ip;
	}

	// Video of the world
	public function worldAction(EntityManagerInterface $em, $language, $themeId, $theme)
	{
		$flags = $em->getRepository(Language::class)->displayFlagWithoutWorld();
		$currentLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $language]);

		$themes = $em->getRepository(Theme::class)->getAllThemesWorld(explode(",", $_ENV["LANGUAGES"]));

		$theme = $em->getRepository(Theme::class)->find($themeId);

		$title = [];

		if(!empty($currentLanguage))
			$title[] = $currentLanguage->getTitle();

		if(!empty($theme))
			$title[] = $theme->getTitle();

		return $this->render('video/Video/world.html.twig', [
			'flags' => $flags,
			'themes' => $themes,
			'title' => implode(" - ", $title),
			'theme' => empty($theme) ? null : $theme
		]);
	}

	public function selectThemeForIndexWorldAction(Request $request, EntityManagerInterface $em, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('Video_World', ['language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
	}

	public function worldDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $language)
	{
		$themeId = $request->query->get("theme_id");
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->all('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];

		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Video::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Video::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].';">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getSameTopicsAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Video::class)->find($id);
		$sameTopics = $em->getRepository(Video::class)->getSameTopics($entity);

		return $this->render("video/Video/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}
}