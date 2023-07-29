<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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

		return $this->render('index/Archive/archive.html.twig', array (
			'datas' => $res,
			'total' => array_sum(array_column($res, "count")),
			'languages' => $languages,
			'currentLanguage' => $currentLanguage
		));
	}
	
	public function archiveThemesAction(Request $request, TranslatorInterface $translator, EntityManagerInterface $em, $className, $language)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

		$className = base64_decode($className);
		
		$locale = empty($language) ? $request->getLocale() : $language;

		$parentTheme = $em->getRepository(Theme::class)->getThemeParent($locale);
		$countTheme = $em->getRepository($className)->countArchivedByTheme($locale);
		$themes = $em->getRepository(Theme::class)->getTheme($locale);

		foreach($themes as $theme)
			if(array_search($theme->getTitle(), array_column($countTheme, 'title')) === false)
				$countTheme[] = ["count" => 0, "title" => $theme->getTitle(), "parentTheme" => $theme->getParentTheme()->getId(), "id" => $theme->getId()];

		usort($countTheme, function($a, $b) {
			return $a['title'] <=> $b['title'];
		});

		return $this->render('index/Archive/archive_theme.html.twig', array (
			'parentTheme' => $parentTheme,
			'nbrArchive' => array_sum(array_column($countTheme, "count")),
			'themes' => $countTheme,
			'className' => base64_encode($className),
			'title' => $translator->trans("index.className.".(new \ReflectionClass(new $className()))->getShortName(), [], 'validators')
		));
	}
	
	public function archiveWitchcraftsAction(Request $request, TranslatorInterface $translator, EntityManagerInterface $em, $className, $language)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

		$className = base64_decode($className);
		
		$locale = empty($language) ? $request->getLocale() : $language;

		$menuGrimoire = $em->getRepository(\App\Entity\MenuGrimoire::class)->getSurThemeGrimoire($request->getLocale());
		$surThemeGrimoires = $em->getRepository(\App\Entity\SurThemeGrimoire::class)->getSurThemeByLanguage($request->getLocale());
		$countTheme = $em->getRepository($className)->countArchivedByTheme($locale);

		foreach($surThemeGrimoires as $surThemeGrimoire)
			if(array_search($surThemeGrimoire->getTitle(), array_column($countTheme, 'title')) === false)
				$countTheme[] = ["count" => 0, "title" => $surThemeGrimoire->getTitle(), "surTheme" => $surThemeGrimoire->getMenuGrimoire()->getId(), "id" => $surThemeGrimoire->getId()];

		usort($countTheme, function($a, $b) {
			return $a['title'] <=> $b['title'];
		});

		return $this->render('index/Archive/archive_witchcraft.html.twig', array (
			'menuGrimoire' => $menuGrimoire,
			'nbrArchive' => array_sum(array_column($countTheme, "count")),
			'themes' => $countTheme,
			'className' => base64_encode($className),
			'title' => $translator->trans("index.className.Grimoire", [], 'validators')
		));
	}

	public function tabarchiveAction(Request $request, $id, $theme, $className)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

		return $this->render('index/Archive/tabArchive.html.twig', array(
			'themeDisplay' => $theme,
			'themeId' => $id,
			'className' => $className
		));		
	}
	
	public function tabarchiveDatatablesAction(Request $request, EntityManagerInterface $em, APImgSize $imgSize, APDate $date, $themeId, $className)
	{
		if(!$this->isGranted('ROLE_ARCHIVIST'))
			return $this->redirect($this->generateUrl("Archive_Index"));

		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i = 0; $i < intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}

		$className = base64_decode($className);
		
        $entities = $em->getRepository($className)->getTabArchive($themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository($className)->getTabArchive($themeId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);

		foreach($entities as $entity)
		{
			$row = [];		
			$row[] = '<a href="'.$this->generateUrl("Archive_Read", ['id' => $entity->getId(), "className" => base64_encode($className)]).'" >'.$entity->getTitle().'</a>';
			$row[] =  $date->doDate($request->getLocale(), $entity->getPublicationDate());

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
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
	
	public function nbrArchiveAction(Request $request, EntityManagerInterface $em)
	{
		$res = [];

		$entities = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

		foreach($entities as $entity) {
			if(method_exists($entity, "getArchive")) {
				$repository = $em->getRepository($entity);
				if(method_exists($repository, "countArchived"))
					$res[] = $em->getRepository($entity)->countArchived($request->getLocale());
			}
		}

		return new Response(array_sum($res));
	}
}