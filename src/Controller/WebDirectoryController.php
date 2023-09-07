<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\WebDirectory;
use App\Service\APImgSize;

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
}