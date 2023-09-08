<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\State;
use App\Entity\Language;
use App\Entity\WebDirectory;
use App\Service\APImgSize;
use App\Form\Type\WebDirectoryUserParticipationType;

class WebDirectoryController extends AbstractController
{
    public function indexAction(EntityManagerInterface $em)
    {
		$entities = $em->getRepository(WebDirectory::class)->findAll();

        return $this->render('webdirectory/WebDirectory/index.html.twig', [
			'entities' => $entities
		]);
    }
	
	public function readAction(EntityManagerInterface $em, $id, $title)
	{
		$entity = $em->getRepository(WebDirectory::class)->find($id);
		
		return $this->render('webdirectory/WebDirectory/read.html.twig', [
			'entity' => $entity
		]);
	}

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
			$logo = $imgSize->adaptImageSize(200, $entity->getAssetImagePath().$entity->getLogo());
			$row = [];
			$row[] = '<img src="'.$request->getBasePath().'/'.$logo[2].'" alt="" style="width: '.$logo[0].'">';
			
			$readUrl = $this->generateUrl("WebDirectory_Read", array("id" => $entity->getId(), "title" => $entity->getTitle()));
			$row[] = '<a href="'.$readUrl.'" alt=""><strong>'.$entity->getTitle().'</strong></a>';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getWebsiteLanguage()->getAssetImagePath().$entity->getWebsiteLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = "<a href='".$entity->getLink()."'>".$translator->trans('directory.index.Visiter', [], 'validators')."</a>";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	// USER PARTICIPATION
    public function newAction(Request $request)
    {
        $entity = new WebDirectory();

        $form = $this->createForm(WebDirectoryUserParticipationType::class, $entity);

        return $this->render('webdirectory/WebDirectory/new.html.twig', [
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