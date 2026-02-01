<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Theme;
use App\Entity\Language;
use App\Service\APImgSize;
use App\Service\APDate;

class ArchiveController extends AbstractController
{
	private function getPathTheme(String $className): String
	{
		switch($className) {
			case "Grimoire":
				return "Archive_Witchcraft";
			default:
				return "Archive_Theme";
		}

		return "";
	}

	#[Route('/archive/{language}', name: 'Archive_Index', defaults: ['language' => null])]
	public function archiveAction(Request $request, TranslatorInterface $translator, EntityManagerInterface $em, $language)
	{
		$languages = $em->getRepository(Language::class)->findAll();

		$res = [];

		$locale = empty($language) ? $request->getLocale() : $language;
		$currentLanguage = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $locale]);

		$entities = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

		foreach($entities as $entity) {
			if(method_exists($entity, "getArchive")) {
				$repository = $em->getRepository($entity);
				if(method_exists($repository, "countArchived"))
					$res[] = ["canonicalName" => $translator->trans("index.className.".(new \ReflectionClass(new $entity()))->getShortName(), [], 'validators'), "className" => base64_encode($entity), "count" => $em->getRepository($entity)->countArchived($locale), "path" => $this->getPathTheme((new \ReflectionClass(new $entity()))->getShortName())];
			}
		}

		usort($res, function($a, $b) {
			return $a['canonicalName'] <=> $b['canonicalName'];
		});

		return $this->render('index/Archive/archive.html.twig', [
			'datas' => $res,
			'total' => array_sum(array_column($res, "count")),
			'languages' => $languages,
			'currentLanguage' => $currentLanguage
		]);
	}

	#[Route('/archive/theme/{className}/{language}', name: 'Archive_Theme', defaults: ['Language' => null])]
	public function archiveThemesAction(Request $request, TranslatorInterface $translator, EntityManagerInterface $em, $className, $language)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

		$className = base64_decode($className);
		$locale = empty($language) ? $request->getLocale() : $language;
		$entities = $em->getRepository($className)->getAllArchivesByThemeAndLanguage($className , $locale);

		$datas = [];

		foreach($entities as $entity)
			$datas[$entity["parentTheme"]][] = $entity;

		$total = 0;

		foreach($datas as $data)
			$total += array_sum(array_column($data, "total"));

		return $this->render('index/Archive/archive_theme.html.twig', [
			'datas' => $datas,
			'nbrArchive' => $total,
			'className' => base64_encode($className),
			'title' => $translator->trans("index.className.".(new \ReflectionClass(new $className()))->getShortName(), [], 'validators')
		]);
	}

	#[Route('/archive/witchcraft/{className}/{language}', name: 'Archive_Witchcraft', defaults: ['Language' => null])]
	public function archiveWitchcraftsAction(Request $request, TranslatorInterface $translator, EntityManagerInterface $em, $className, $language)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

		$className = base64_decode($className);
		
		$locale = empty($language) ? $request->getLocale() : $language;
		
		$entities = $em->getRepository($className)->getAllArchivesByThemeAndLanguage($className, $locale);

		$datas = [];

		foreach($entities as $entity)
			$datas[$entity["parentTheme"]][] = $entity;

		$total = 0;

		foreach($datas as $data)
			$total += array_sum(array_column($data, "total"));

		return $this->render('index/Archive/archive_witchcraft.html.twig', [
			"datas" => $datas,
			'nbrArchive' => $total,
			'className' => base64_encode($className),
			'title' => $translator->trans("index.className.Grimoire", [], 'validators')
		]);
	}

	#[Route('/archive/tab/{id}/{theme}/{className}', name: 'Archive_Tab', requirements: ['theme' => ".+"])]
	public function tabarchiveAction(Request $request, $id, $theme, $className)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

		return $this->render('index/Archive/tabArchive.html.twig', [
			'themeDisplay' => $theme,
			'themeId' => $id,
			'className' => $className
		]);
	}

	#[Route('/tabarchivedatatables/{themeId}/{className}', name: 'Archive_Datatables')]
	public function tabarchiveDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $themeId, $className)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

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

		$className = base64_decode($className);

        $entities = $em->getRepository($className)->getTabArchive($themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository($className)->getTabArchive($themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];		
			$row[] = '<a href="'.$this->generateUrl("Archive_Read", ['id' => $entity->getId(), "className" => base64_encode($className)]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/archive/read/{id}/{className}', name: 'Archive_Read')]
	public function readAction(EntityManagerInterface $em, $id, $className)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

		$className = base64_decode($className);

		$entity = $em->getRepository($className)->find($id);

		return $this->render("index/Archive/readArchive.html.twig", [
			"entity" => $entity,
			"className" => (new \ReflectionClass(new $className()))->getShortName()
		]);
	}
}