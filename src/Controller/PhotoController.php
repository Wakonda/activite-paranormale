<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Theme;
use App\Entity\Photo;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\FileManagement;
use App\Service\APImgSize;
use App\Service\APDate;
use App\Service\APHtml2Pdf;
use App\Form\Type\PhotoUserParticipationType;

class PhotoController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em)
    {
		$locale = $request->getLocale();

		$entities = $em->getRepository(Photo::class)->getAllPhotoByThemeAndLanguage($locale);
		$nbrPicture = $em->getRepository(Photo::class)->nbrPicture($locale);

		$datas = [];

		foreach($entities as $entity)
			$datas[$entity["parentTheme"]][] = $entity;

		return $this->render('photo/Photo/index.html.twig', [
			'datas' => $datas,
			'nbrPicture' => $nbrPicture
		]);
    }
	
	public function tabPictureAction(Request $request, $id, $theme)
	{
		return $this->render('photo/Photo/tabPicture.html.twig', [
			'themeDisplay' => $theme,
			'themeId' => $id
		]);
	}

	public function tabPictureDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $themeId)
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

        $entities = $em->getRepository(Photo::class)->getTabPicture($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Photo::class)->getTabPicture($themeId, $language, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];

			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].';">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function readAction(Request $request, EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Photo::class)->find($id);

		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$previousAndNextEntities = $em->getRepository(Photo::class)->getPreviousAndNextEntities($entity, $request->getLocale());

		return $this->render('photo/Photo/readPicture.html.twig', [
			'previousAndNextEntities' => $previousAndNextEntities,
			'entity' => $entity
		]);
	}

	public function countByLanguage(EntityManagerInterface $em, Request $request)
	{
		return new Response($em->getRepository(Photo::class)->nbrPicture($request->getLocale()));
	}

	// INDEX
	public function sliderAction(Request $request, EntityManagerInterface $em)
	{
		$entities = $em->getRepository(Photo::class)->getSliderNew($request->getLocale());
		return $this->render("photo/Photo/slider.html.twig", [
			"entities" => $entities
		]);
	}

	// ENREGISTREMENT PDF
	public function pdfVersionAction(EntityManagerInterface $em, APHtml2Pdf $html2pdf, $id)
	{
		$entity = $em->getRepository(Photo::class)->find($id);
		
		if(empty($entity))
			throw $this->createNotFoundException("The photo does not exist");
		
		if($entity->getArchive())
			throw new GoneHttpException('Archived');

		$content = $this->render("photo/Photo/pdfVersion.html.twig", ["entity" => $entity]);
		
		return $html2pdf->generatePdf($content->getContent());
	}

	// Photo of the world
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

		return $this->render('photo/Photo/world.html.twig', [
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

		return new Response($this->generateUrl('Photo_World', array('language' => $language, 'themeId' => $theme->getId(), 'theme' => $theme->getTitle())));
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

        $entities = $em->getRepository(Photo::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Photo::class)->getDatatablesForWorldIndex($language, $themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$photo = $imgSize->adaptImageSize(150, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = '<img src="'.$request->getBasePath().'/'.$photo[2].'" alt="" style="width: '.$photo[0].'; height:'.$photo[1].'">';			
			$row[] = '<a href="'.$this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug())).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	
	public function getSameTopicsAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Photo::class)->find($id);
		$sameTopics = $em->getRepository(Photo::class)->getSameTopics($entity);
		
		return $this->render("photo/Photo/sameTopics.html.twig", ["sameTopics" => $sameTopics]);
	}

	// USER PARTICIPATION
    public function newAction(Request $request)
    {
        $entity = new Photo();

        $form = $this->createForm(PhotoUserParticipationType::class, $entity, ["language" => $request->getLocale()]);

        return $this->render('photo/Photo/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView()
        ]);
    }
	
	public function createAction(Request $request, EntityManagerInterface $em)
    {
		return $this->genericCreateUpdate($request, $em);
    }

	private function genericCreateUpdate(Request $request, EntityManagerInterface $em, $id = 0)
	{
		$locale = $request->getLocale();
		$user = $this->getUser();

		if(empty($id))
			$entity = new Photo();
		else {
			$entity = $em->getRepository(Photo::class)->find($id);

			if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
				throw new \Exception("You are not authorized to edit this document.");
		}

        $form = $this->createForm(PhotoUserParticipationType::class, $entity, ["language" => $locale]);
        $form->handleRequest($request);

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $locale]);
		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);

		$entity->setState($state);
		$entity->setLanguage($language);

		if(is_object($user)) {
			if($entity->getIsAnonymous() == 1) {
				if($form->get('validate')->isClicked())
					$user = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);

				$entity->setAuthor($user);
				$entity->setPseudoUsed("Anonymous");
			}
			else {
				$entity->setAuthor($user);
				$entity->setPseudoUsed($user->getUsername());
			}
		}
		else {
			$user = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);
			$entity->setAuthor($user);
			$entity->setIsAnonymous(0);
		}

        if ($form->isValid()) {
			if(is_object($ci = $entity->getIllustration())) {
				$titleFile = uniqid()."_".$ci->getClientOriginalName();
				$illustration = new FileManagement();
				$illustration->setTitleFile($titleFile);
				$illustration->setRealNameFile($titleFile);
				$illustration->setExtensionFile(pathinfo($ci->getClientOriginalName(), PATHINFO_EXTENSION));
				
				$ci->move($entity->getTmpUploadRootDir(), $titleFile);
				
				$entity->setIllustration($illustration);
			}

			$em->persist($entity);
			$em->flush();
			
			return $this->redirect($this->generateUrl('Photo_Validate', ['id' => $entity->getId()]));
        }

        return $this->render('photo/Photo/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView()
        ]);
	}

    public function editAction(Request $request, EntityManagerInterface $em, $id)
    {
		$user = $this->getUser();

        $entity = $em->getRepository(Photo::class)->find($id);

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		if($entity->getState()->isStateDisplayed() or $user->getId() != $entity->getAuthor()->getId())
			throw new \Exception("You are not authorized to edit this document.");

        $form = $this->createForm(PhotoUserParticipationType::class, $entity, ["language" => $request->getLocale()]);

        return $this->render('photo/Photo/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView()
        ]);
    }

	public function updateAction(Request $request, EntityManagerInterface $em, $id)
    {
		return $this->genericCreateUpdate($request, $em, $id);
    }

	public function validateAction(Request $request, EntityManagerInterface $em, $id)
	{
        $entity = $em->getRepository(Photo::class)->find($id);

		if($entity->getState()->isRefused() or $entity->getState()->isDuplicateValues())
			throw new AccessDeniedHttpException("You can't edit this document.");

		$user = $this->getUser();

		if($entity->getState()->isStateDisplayed() or (!empty($entity->getAuthor()) and !$this->isGranted('IS_AUTHENTICATED_ANONYMOUSLY') and $user->getId() != $entity->getAuthor()->getId()))
			throw new AccessDeniedHttpException("You are not authorized to edit this document.");

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);
		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);

		$entity->setState($state);
		$em->persist($entity);
		$em->flush();

		return $this->render('photo/Photo/validate_externaluser_text.html.twig');
	}
}