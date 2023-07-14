<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\TagWord;
use App\Entity\Tags;
use App\Entity\Language;
use App\Service\APDate;
use App\Service\APImgSize;

class TagsController extends AbstractController
{
	public function index() {
		return $this->render("tags/Tags/index.html.twig");
	}

	public function listDatatables(Request $request, APImgSize $imgSize)
	{
		$em = $this->getDoctrine()->getManager();

		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = $request->query->get('search')["value"];

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($order = $request->query->get('order')); $i++)
		{
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(TagWord::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(TagWord::class)->getDatatablesForIndex($request->getLocale(), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$img = empty($entity->getPhotoIllustrationFilename()) ? null : $entity->getAssetImagePath().$entity->getPhotoIllustrationFilename();
			$img = $imgSize->adaptImageSize(250, $img);

			$row = [];
			$row[] = '<a href="'.$this->generateUrl("ap_tags_search", ['id' => $entity->getId(), 'title' => $entity->getTitle()]).'" >'.$entity->getTitle().'</a>';
			$row[] = '<img src="'.$request->getBasePath().'/'.$img[2].'" alt="" style="width: '.$img[0].';">';

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

    public function searchAction(Request $request, $id, $title)
    {
		$em = $this->getDoctrine()->getManager();
		
		$tagWord = $em->getRepository(TagWord::class)->find($id);
		$countEntities = $em->getRepository(Tags::class)->getEntitiesByTags($id, $request->getLocale(), true);

        return $this->render('tags/Tags/search.html.twig', ['tagWord' => $tagWord, 'countEntities' => $countEntities]);
    }

	public function searchDatatablesAction(Request $request, TranslatorInterface $translator, APDate $date, $id, $title)
	{
		$em = $this->getDoctrine()->getManager();

		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');
		
		$locale = $request->getLocale();

		$tagWord = $em->getRepository(TagWord::class)->find($id);
        $entities = $em->getRepository(Tags::class)->getDatatablesForSearchTagsAdmin($tagWord, $locale, $iDisplayStart, $iDisplayLength, $sSearch);
		$iTotal = $em->getRepository(Tags::class)->getDatatablesForSearchTagsAdmin($tagWord, $locale, $iDisplayStart, $iDisplayLength, $sSearch, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = ((method_exists($entity->getEntity(), "getArchive") and $entity->getEntity()->getArchive()) ? '<i class="fas fa-key text-warning"></i>' : '').$translator->trans('index.className.'.$entity->getEntity()->getRealClass(), [], 'validators');
			$row[] = '<a href="'.$this->generateUrl($entity->getEntity()->getShowRoute(), ['id' => $entity->getEntity()->getId(), 'title_slug' => $entity->getEntity()->getUrlSlug()]).'" >'.$entity->getEntity()->getTitle().'</a>'.((method_exists($entity->getEntity(), "getSubTitle") and !empty($st = $entity->getEntity()->getSubTitle())) ? "<br>".$st : "");
			$row[] = (!empty($theme = $entity->getEntity()->getTheme())) ? $theme->getTitle() : "";
			$row[] =  $date->doDate($request->getLocale(), $entity->getEntity()->getPublicationDate());

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function TagsAvailableEditAction(Request $request)
	{
		$em = $this->getDoctrine()->getManager();
		$language = null;
		if($request->query->has('locale') and !empty($locale = $request->query->get('locale')))
			$language = $em->getRepository(Language::class)->find($locale);

		$locale = (!empty($language)) ? $language->getAbbreviation() : $request->getLocale();
		$search = $request->query->get('search');
		
		$tagWords = $em->getRepository(TagWord::class)->searchTagWordAdmin($search, $locale);
		
		$res = [];
		
		foreach($tagWords as $tagWord)
			$res[] = $tagWord->getTitle();

		return new JsonResponse($res);
	}
}