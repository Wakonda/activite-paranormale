<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\State;
use App\Entity\Language;
use App\Entity\WebDirectory;
use App\Service\APImgSize;
use App\Form\Type\WebDirectoryUserParticipationType;

class WebDirectoryController extends AbstractController
{
	#[Route('/directory', name: 'WebDirectory_Index')]
    public function indexAction(EntityManagerInterface $em)
    {
		$entities = $em->getRepository(WebDirectory::class)->findAll();

        return $this->render('webdirectory/WebDirectory/index.html.twig', [
			'entities' => $entities
		]);
    }

	#[Route('/directory/read/{id}/{title}', name: 'WebDirectory_Read')]
	public function readAction(EntityManagerInterface $em, $id, $title)
	{
		$entity = $em->getRepository(WebDirectory::class)->find($id);

		return $this->render('webdirectory/WebDirectory/read.html.twig', [
			'entity' => $entity
		]);
	}

	#[Route('/searchdatatables', name: 'WebDirectory_SearchDatatables')]
	public function searchDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, APImgSize $imgSize)
	{
		$language = $request->getLocale();

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

        $entities = $em->getRepository(WebDirectory::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $language);
		$iTotal = $em->getRepository(WebDirectory::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $language, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
	
			$logo = $imgSize->adaptImageSize(200, $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename());
			$row[] = '<img src="'.$request->getBasePath().'/'.$logo[2].'" alt="" style="width: '.$logo[0].'">';
			
			$readUrl = $this->generateUrl("WebDirectory_Read", array("id" => $entity->getId(), "title" => $entity->getTitle()));
			$row[] = '<a href="'.$readUrl.'" alt="'.addslashes($entity->getTitle()).'"><strong>'.$entity->getTitle().'</strong></a>';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getWebsiteLanguage()->getAssetImagePath().$entity->getWebsiteLanguage()->getLogo().'" alt="'.addslashes($entity->getWebsiteLanguage()->getTitle()).'" width="20" height="13">';
			$row[] = "<a href='".$entity->getLink()."'>".$translator->trans('directory.index.Visiter', [], 'validators')."</a>";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	// USER PARTICIPATION
	#[Route('/directory/published', name: 'WebDirectory_User_New')]
    public function newAction(Request $request)
    {
        $entity = new WebDirectory();

        $form = $this->createForm(WebDirectoryUserParticipationType::class, $entity);

        return $this->render('webdirectory/WebDirectory/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView()
        ]);
    }

	#[Route('/directory/published/create', name: 'WebDirectory_User_Create')]
	public function createAction(Request $request, EntityManagerInterface $em)
    {
		return $this->genericCreateUpdate($request, $em);
    }

	private function genericCreateUpdate(Request $request, EntityManagerInterface $em, $id = 0)
	{
		$locale = $request->getLocale();
		$user = $this->getUser();

		if(empty($id))
			$entity = new WebDirectory();
		else {
			$entity = $em->getRepository(WebDirectory::class)->find($id);
		}

        $form = $this->createForm(WebDirectoryUserParticipationType::class, $entity);
        $form->handleRequest($request);

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $locale]);
		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);

		$entity->setState($state);
		$entity->setLanguage($language);

        if ($form->isValid()) {
			if(is_object($ci = $entity->getIllustration()))
			{
				$titleFile = uniqid()."_".$ci->getClientOriginalName();
				$illustration = new \App\Entity\FileManagement();
				$illustration->setTitleFile($titleFile);
				$illustration->setRealNameFile($titleFile);
				$illustration->setExtensionFile(pathinfo($ci->getClientOriginalName(), PATHINFO_EXTENSION));
				
				$ci->move($entity->getTmpUploadRootDir(), $titleFile);
				
				$entity->setIllustration($illustration);
			}

			$em->persist($entity);
			$em->flush();

			return $this->render('webdirectory/WebDirectory/validate_externaluser_text.html.twig');
        }

        return $this->render('webdirectory/WebDirectory/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView()
        ]);
	}
}