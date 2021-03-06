<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\WebDirectory;
use App\Service\APImgSize;

class WebDirectoryController extends AbstractController
{
    public function indexAction()
    {
		$em = $this->getDoctrine()->getManager();
		$entities = $em->getRepository(WebDirectory::class)->findAll();
		
        return $this->render('webdirectory/WebDirectory/index.html.twig', array(
			'entities' => $entities
		));
    }
	
	public function readAction($id, $title)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(WebDirectory::class)->find($id);
		
		return $this->render('webdirectory/WebDirectory/read.html.twig', array(
			'entity' => $entity
		));
	}

	public function searchDatatablesAction(Request $request, TranslatorInterface $translator, APImgSize $imgSize)
	{
		$em = $this->getDoctrine()->getManager();

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
		
        $entities = $em->getRepository(WebDirectory::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(WebDirectory::class)->getDatatablesForIndex($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => array()
		);

		foreach($entities as $entity)
		{
			$logo = $imgSize->adaptImageSize(200, $entity->getAssetImagePath().$entity->getLogo());
			$row = array();
			$row[] = '<img src="'.$request->getBasePath().'/'.$logo[2].'" alt="" style="width: '.$logo[0].'">';
			
			$readUrl = $this->generateUrl("WebDirectory_Read", array("id" => $entity->getId(), "title" => $entity->getTitle()));
			$row[] = '<a href="'.$readUrl.'" alt=""><strong>'.$entity->getTitle().'</strong></a>';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="" width="20" height="13">';
			$row[] = "<a href='".$entity->getLink()."'>".$translator->trans('directory.index.Visiter', array(), 'validators')."</a>";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
}