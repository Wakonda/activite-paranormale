<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Biography;
use App\Entity\Document;
use App\Entity\DocumentFamily;
use App\Entity\Theme;
use App\Form\Type\DocumentSearchType;
use App\Service\APDate;

class DocumentController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, $themeId, $theme)
    {
		$theme = $em->getRepository(Theme::class)->find($themeId);
		$obj = new \stdclass();
		$obj->title = null;
		$obj->theme = $theme;
		$obj->documentFamily = null;
		$form = $this->createForm(DocumentSearchType::class, $obj, ["locale" => $request->getLocale()]);
		
		return $this->render('document/Document/index.html.twig', [
			"form" => $form->createView()
		]);
    }

	public function listDatatablesAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
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

		$form = $this->createForm(DocumentSearchType::class, null, ["locale" => $request->getLocale()]);
		
		parse_str($request->query->get($form->getName()), $datas);

		$form->submit($datas[$form->getName()]);
		
        $entities = $em->getRepository(Document::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $form->getData());
		$iTotal = $em->getRepository(Document::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $form->getData(), true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];
		
		foreach($entities as $entity)
		{
			$row = [];
			$row[] = '<a href="'.$this->generateUrl('DocumentBundle_AbstractDocument', ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'" alt="">'.$entity->getTitle().'</a>';

			foreach($entity->getAuthorDocumentBiographies() as $authorDocumentBiography) {
				$correctBio = $em->getRepository(Biography::class)->getBiographyInCorrectLanguage($authorDocumentBiography, $request->getLocale());
				if (!empty($correctBio))
					$row[] = '<p><a href="'.$this->generateUrl('Biography_Show', ['id' => $correctBio->getId(), 'title' => $correctBio->getTitle()]).'" alt="">'.$correctBio->getTitle().'</a></p>';
				else
					$row[] = '<p>'.$authorDocumentBiography->getTitle().'</p>';
			}
			
			$row[] = !empty($entity->getReleaseDateOfDocument()) ? explode("-", $entity->getReleaseDateOfDocumentText())[0] : $translator->trans($entity->getReleaseDateOfDocumentText(), [], "validators");
			
			$internationalName = (empty($df = $entity->getDocumentFamily())) ? "" : $df->getInternationalName();
			$row[] = !empty($df = $em->getRepository(DocumentFamily::class)->getDocumentFamilyRealNameByInternationalNameAndLanguage($internationalName, $request->getLocale())) ? $df->getTitle() : '';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt=""width="20" height="13" />';

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function selectThemeForIndexAction(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_id');

		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('Document_Index', ['themeId' => $theme->getId(), 'theme' => $theme->getTitle()]));
	}
	
	public function abstractDocumentAction(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Document::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('document/Document/abstractDocument.html.twig', [
			"entity" => $entity
		]);
	}
	
	public function downloadDocumentAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Document::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));

		$response = new Response();
		$response->setContent(file_get_contents($entity->getAssetImagePath().$entity->getPdfDoc()));

		$response->headers->set('Content-type', mime_content_type($entity->getAssetImagePath().$entity->getPdfDoc()));
		$response->headers->set('Content-Disposition', 'attachment; filename="'.$entity->getPdfDoc().'"');
		$response->headers->set("Content-Transfer-Encoding", "Binary");
		
		return $response;
	}
	
	public function readDocumentAction(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Document::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('document/Document/readDoc.html.twig', [
			'entity' => $entity
		]);
	}

	public function countDocumentAction(EntityManagerInterface $em)
	{
		$countDocument = $em->getRepository(Document::class)->countDocument();
		return new Response($countDocument);
	}
}