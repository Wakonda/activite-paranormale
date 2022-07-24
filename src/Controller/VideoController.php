<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

use App\Entity\Video;
use App\Entity\Contact;
use App\Entity\Theme;
use App\Entity\SurTheme;
use App\Entity\Language;
use App\Form\Type\VideoAdminType;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;

class VideoController extends AbstractController
{
    public function indexAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		
		$lang = $request->getLocale();
		
		$SurTheme = $em->getRepository(SurTheme::class)->getSurTheme($lang);
		
		$theme2 = $em->getRepository(Theme::class)->getTheme($lang);

		$nbrTheme = $em->getRepository(Theme::class)->nbrTheme($lang);
		$nbrVideo = $em->getRepository(Video::class)->nbrVideo($lang);

		for($i = 0; $i < $nbrTheme; $i++)
		{
			$nbrArchiveParTheme[$i] = $em->getRepository(Video::class)->nbrArchiveParTheme($lang, $theme2[$i]->gettitle());
			$tabThemeNbr[$i][0] = $theme2[$i]->getTitle();
			$tabThemeNbr[$i][1] = $nbrArchiveParTheme[$i];
		}
		return $this->render('video/Video/index.html.twig', array(
			'surTheme' => $SurTheme,
			'nbrVideo' => $nbrVideo,
			'tabThemeNbr' => $tabThemeNbr,
			'nbrTheme' => $nbrTheme,
			'theme' => $theme2
		));
    }
	
	public function tabVideoAction(Request $request, $id, $theme)
	{
		return $this->render('video/Video/tabVideo.html.twig', array(
			'themeDisplay' => $theme,
			'themeId' => $id
		));	
	}

	public function tabVideoDatatablesAction(Request $request, APImgSize $imgSize, APDate $date, $themeId)
	{
		$em = $this->getDoctrine()->getManager();

		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');
		$language = $request->getLocale();

		$sortByColumn = array();
		$sortDirColumn = array();
			
		for($i = 0; $i < intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}
		
        $entities = $em->getRepository(Video::class)->getTabVideo($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Video::class)->getTabVideo($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => array()
		);

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
			$row = array();
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function readAction(Request $request, $id, $title_slug)
	{
		$em = $this->getDoctrine()->getManager();
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
		
		return $this->render('video/Video/readVideo.html.twig', array(
			'previousAndNextEntities' => $previousAndNextEntities,
			'video' => $entity
		));
	}
	
	public function notifyDeletedVideoAction(Request $request, SessionInterface $session, \Swift_Mailer $mailer, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$video = $em->getRepository(Video::class)->find($id);
	
		$entity  = new Contact();
		$entity->setDateContact(new \DateTime("now"));
		$entity->setStateContact(0);

		$entity->setPseudoContact($this->getClientIp($request));
		$entity->setMessageContact("Avertissement : Vidéo potentiellement supprimée => <a href='".$this->generateUrl('Video_Read', array("id" => $video->getId(), "title_slug" => $video->getUrlSlug()), UrlGeneratorInterface::ABSOLUTE_URL)."'>".$video->getTitle()."</a>");
		$entity->setEmailContact($_ENV["MAILER_CONTACT"]);
		$entity->setSubjectContact("Suppression d'une vidéo");
		
		$message = (new \Swift_Message("Suppression d'une vidéo"))
			->setTo($_ENV["MAILER_CONTACT"])
			->setFrom([$_ENV["MAILER_CONTACT"]])
			->setBody($this->renderView('contact/Contact/mail.html.twig', array('entity' => $entity)), 'text/html')
		;
		$mailer->send($message);
		
		$em->persist($entity);
		$em->flush();
		
        $session->getFlashBag()->add(
            'notice',
            'Votre requête a bien été envoyée !'
        );
		
		return $this->redirect($this->generateUrl('Video_Read', array("id" => $video->getId(), "title_slug" => $video->getUrlSlug())));
	}
	
	public function countVideoAction($lang)
	{
		$em = $this->getDoctrine()->getManager();
		$nbrTabVideo = $em->getRepository(Video::class)->nbrVideo($lang);
		return new Response($nbrTabVideo);
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(APHtml2Pdf $html2pdf, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Video::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The video does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("video/Video/pdfVersion.html.twig", array("entity" => $entity));

		return $html2pdf->generatePdf($content->getContent());
	}
	
	public function embeddedAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Video::class)->find($id);
		
        return $this->render('video/Video/embedded.html.twig', array(
            'entity' => $entity
        ));
	}

	// INDEX
	public function sliderAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Video::class)->getSliderNew($request->getLocale());
		return $this->render("video/Video/slider.html.twig", array(
			"entity" => $entity
		));
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
	public function worldAction($language, $themeId, $theme)
	{
		$em = $this->getDoctrine()->getManager();
		$flags = $em->getRepository(Language::class)->displayFlagWithoutWorld();
		$currentLanguage = $em->getRepository(Language::class)->findOneBy(array("abbreviation" => $language));

		$themes = $em->getRepository(Theme::class)->getAllThemesWorld(explode(",", $_ENV["LANGUAGES"]));

		$theme = $em->getRepository(Theme::class)->find($themeId);

		$title = [];

		if(!empty($currentLanguage))
			$title[] = $currentLanguage->getTitle();

		if(!empty($theme))
			$title[] = $theme->getTitle();

		return $this->render('video/Video/world.html.twig', array(
			'flags' => $flags,
			'themes' => $themes,
			'title' => implode(" - ", $title),
			'theme' => empty($theme) ? null : $theme
		));	
	}

	public function selectThemeForIndexWorldAction(Request $request, $language)
	{
		$themeId = $request->request->get('theme_id');
		$language = $request->request->get('language', 'all');

		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(Theme::class)->find($themeId);
		return new Response($this->generateUrl('Video_World', array('language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle())));
	}

	public function worldDatatablesAction(Request $request, APImgSize $imgSize, APDate $date, $language)
	{
		$em = $this->getDoctrine()->getManager();
		$themeId = $request->query->get("theme_id");
		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');

		$sortByColumn = array();
		$sortDirColumn = array();
			
		for($i=0 ; $i<intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}
		
        $entities = $em->getRepository(Video::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Video::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => array()
		);

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhoto());
			$row = array();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getSameTopicsAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Video::class)->find($id);
		$sameTopics = $em->getRepository(Video::class)->getSameTopics($entity);
		
		return $this->render("video/Video/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}
}