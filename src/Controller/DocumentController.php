<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
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
	#[Route('/document/{themeId}/{theme}', name: 'Document_Index', defaults: ['themeId' => 0, 'theme' => null], requirements: ['themeId' => '\d+', 'theme' => '.+'])]
    public function index(Request $request, EntityManagerInterface $em, $themeId, $theme)
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

	#[Route('/document/listdatatables', name: 'Document_ListDatatables')]
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

		$datas = $form->getData();

		if($request->query->has("action") and $request->query->get("action") == "reset")
			$datas = [];

        $entities = $em->getRepository(Document::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $datas);
		$iTotal = $em->getRepository(Document::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $datas, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];
		
		foreach($entities as $entity)
		{
			$row = [];
			$row[] = '<a href="'.$this->generateUrl('DocumentBundle_AbstractDocument', ['id' => $entity->getId(), 'title_slug' => $entity->getUrlSlug()]).'">'.$entity->getTitle().'</a>';

			foreach($entity->getAuthorDocumentBiographies() as $authorDocumentBiography) {
				$correctBio = $em->getRepository(Biography::class)->getBiographyInCorrectLanguage($authorDocumentBiography, $request->getLocale());
				if (!empty($correctBio))
					$row[] = '<p><a href="'.$this->generateUrl('Biography_Show', ['id' => $correctBio->getId(), 'title_slug' => $correctBio->getSlug()]).'">'.$correctBio->getTitle().'</a></p>';
				else
					$row[] = '<p>'.$authorDocumentBiography->getTitle().'</p>';
			}
			
			$row[] = !empty($entity->getReleaseDateOfDocument()) ? explode("-", $entity->getReleaseDateOfDocumentText())[0] : $translator->trans($entity->getReleaseDateOfDocumentText(), [], "validators");
			
			$internationalName = (empty($df = $entity->getDocumentFamily())) ? "" : $df->getInternationalName();
			$row[] = !empty($df = $em->getRepository(DocumentFamily::class)->getDocumentFamilyRealNameByInternationalNameAndLanguage($internationalName, $request->getLocale())) ? $df->getTitle() : '';
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="'.addslashes($entity->getLanguage()->getTitle()).'" width="20" height="13" />';

			$output['data'][] = $row;
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	#[Route('/document/abstract/{id}/{title_slug}', name: 'DocumentBundle_AbstractDocument', defaults: ['title_slug' => null], requirements: ['id' => '\d+'])]
	public function abstractDocument(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Document::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('document/Document/abstractDocument.html.twig', [
			"entity" => $entity
		]);
	}

	#[Route('/document/download/{id}', name: 'DocumentBundle_DownloadDocument', requirements: ['id' => '\d+'])]
	public function downloadDocument(EntityManagerInterface $em, $id)
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

	#[Route('/document/read/{id}/{title_slug}', name: 'DocumentBundle_ReadDocument', defaults: ['title_slug' => null], requirements: ['id' => '\d+'])]
	public function readDocument(EntityManagerInterface $em, $id, $title_slug)
	{
		$entity = $em->getRepository(Document::class)->find($id);
		
		if($entity->getArchive())
			return $this->redirect($this->generateUrl("Archive_Read", ["id" => $entity->getId(), "className" => base64_encode(get_class($entity))]));
		
		return $this->render('document/Document/readDoc.html.twig', [
			'entity' => $entity
		]);
	}
}