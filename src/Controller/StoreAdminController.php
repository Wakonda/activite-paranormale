<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use Doctrine\ORM\Query\ResultSetMapping;

use App\Entity\Stores\Store;
use App\Entity\Stores\BookStore;
use App\Entity\Stores\AlbumStore;
use App\Entity\Stores\MovieStore;
use App\Entity\Stores\TelevisionSerieStore;
use App\Entity\Stores\WitchcraftToolStore;
use App\Entity\Language;
use App\Form\Type\StoreAdminType;
use App\Service\ConstraintControllerValidator;

/**
 * Store controller.
 *
 */
class StoreAdminController extends AdminGenericController
{
	protected $entityName = 'Store';
	protected $className = Store::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "Store_Admin_Index"; 
	protected $showRoute = "Store_Admin_Show";
	protected $formName = 'ap_store_storeadmintype';

	private function getDataClass(String $category): String
	{
		switch($category)
		{
			case "book":
				return BookStore::class;
			case "album":
				return AlbumStore::class;
			case "movie":
				return MovieStore::class;
			case "televisionSerie":
				return TelevisionSerieStore::class;
			case "witchcraftTool":
				return WitchcraftToolStore::class;
			default:
				return Store::class;
		}
	}

	private function setEntity($entity, String $category, Int $id)
	{
		$em = $this->getDoctrine()->getManager();
		
		switch($category)
		{
			case "book":
				$entity->setBook($em->getRepository("App\Entity\BookEdition")->find($id));
				$entity->setTitle($entity->getBook()->getBook()->getTitle());
				break;
			case "album":
				$entity->setAlbum($em->getRepository("App\Entity\Album")->find($id));
				$entity->setTitle($entity->getAlbum()->getTitle());
				break;
			case "movie":
				$entity->setMovie($em->getRepository("App\Entity\Movies\Movie")->find($id));
				$entity->setTitle($entity->getMovie()->getTitle());
				break;
			case "televisionSerie":
				$entity->setTelevisionSerie($em->getRepository("App\Entity\Movies\TelevisionSerie")->find($id));
				$entity->setTitle($entity->getTelevisionSerie()->getTitle());
				break;
			case "witchcraftTool":
				$entity->setWitchcraftTool($em->getRepository("App\Entity\WitchcraftTool")->find($id));
				$entity->setTitle($entity->getWitchcraftTool()->getTitle());
				break;
		}
	}

	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidationAction($form, $entityBindded)
	{
	}

    public function indexAction()
    {
		$twig = 'store/StoreAdmin/index.html.twig';
		return $this->indexGenericAction($twig);
    }
	
    public function showAction($id)
    {
		$twig = 'store/StoreAdmin/show.html.twig';
		return $this->showGenericAction($id, $twig);
    }

    public function newAction(Request $request, String $category)
    {
		$class = $this->getDataClass($category);
		$formType = StoreAdminType::class;
		$entity = new $class();
		$entity->setCategory($category);
		
		if ($request->query->has("id"))
			$this->setEntity($entity, $category, $request->query->get("id"));

		$twig = 'store/StoreAdmin/new.html.twig';
		return $this->newGenericAction($request, $twig, $entity, $formType, ['locale' => $request->getLocale(), "data_class" => $class]);
    }
	
    public function createAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $category)
    {
		$class = $this->getDataClass($category);
		$formType = StoreAdminType::class;
		$entity = new $class();
		$entity->setCategory($category);

		$twig = 'store/StoreAdmin/new.html.twig';
		return $this->createGenericAction($request, $ccv, $translator, $twig, $entity, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName), "data_class" => $class]);
    }
	
    public function editAction(Request $request, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository(Store::class)->find($id);
		$class = $this->getDataClass($entity->getCategory());
		$formType = StoreAdminType::class;

		$twig = 'store/StoreAdmin/edit.html.twig';
		return $this->editGenericAction($id, $twig, $formType, ['locale' => $entity->getLanguage()->getAbbreviation(), "data_class" => $class]);
    }
	
	public function updateAction(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$entity = $this->getDoctrine()->getManager()->getRepository(Store::class)->find($id);
		$class = $this->getDataClass($entity->getCategory());
		$formType = StoreAdminType::class;
		
		$twig = 'store/StoreAdmin/edit.html.twig';
		return $this->updateGenericAction($request, $ccv, $translator, $id, $twig, $formType, ['locale' => $this->getLanguageByDefault($request, $this->formName), "data_class" => $class]);
    }
	
    public function deleteAction($id)
    {
		return $this->deleteGenericAction($id);
    }
	
	public function indexDatatablesAction(Request $request, TranslatorInterface $translator, String $type)
	{
		$em = $this->getDoctrine()->getManager();

		$iDisplayStart = $request->query->get('iDisplayStart');
		$iDisplayLength = $request->query->get('iDisplayLength');
		$sSearch = $request->query->get('sSearch');

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($request->query->get('iSortingCols')); $i++)
		{
			if ($request->query->get('bSortable_'.intval($request->query->get('iSortCol_'.$i))) == "true" )
			{
				$sortByColumn[] = $request->query->get('iSortCol_'.$i);
				$sortDirColumn[] = $request->query->get('sSortDir_'.$i);
			}
		}
		
		// Search on individual column
		$searchByColumns = [];
		$iColumns = $request->query->get('iColumns');

		for($i=0; $i < $iColumns; $i++)
		{
			$searchByColumns[] = $request->query->get('sSearch_'.$i);
		}

        $entities = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $type);
		$iTotal = $em->getRepository($this->className)->getDatatablesForIndexAdmin($iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, $searchByColumns, $type, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			
			$row[] = $entity->getId();
			$row[] = $entity->getTitle();
			$row[] = ucfirst($entity->getPlatform());
			$row[] = $translator->trans('store.admin.'.ucfirst($entity->getCategory()), [], 'validators');
			$row[] = "
			 <a href='".$this->generateUrl('Store_Admin_Show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', array(), 'validators')."</a><br />
			 <a href='".$this->generateUrl('Store_Admin_Edit', array('id' => $entity->getId()))."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', array(), 'validators')."</a><br />";

			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function autocompleteAction(Request $request)
	{
		$query = $request->query->get("q", null);
		$locale = $request->query->get("locale", null);
		$fieldName = $request->query->get("field_name", null);

		switch($fieldName)
		{
			case "book":
				$datas =  $this->getDoctrine()->getManager()->getRepository(Store::class)->getAutocompleteBook($locale, $query);
				break;
			case "album":
				$datas =  $this->getDoctrine()->getManager()->getRepository(Store::class)->getAutocompleteAlbum($locale, $query);
				break;
			case "movie":
				$datas =  $this->getDoctrine()->getManager()->getRepository(Store::class)->getAutocompleteMovie($locale, $query);
				break;
			case"televisionSerie":
				$datas =  $this->getDoctrine()->getManager()->getRepository(Store::class)->getAutocompleteTelevisionSerie($locale, $query);
				break;
			case "witchcraftTool":
				$datas =  $this->getDoctrine()->getManager()->getRepository(Store::class)->getAutocompleteWitchcraftToolStore($locale, $query);
				break;
		}
		
		$results = array();
		
		foreach($datas as $data)
		{
			$obj = new \stdClass();
			$obj->id = $data["id"];
			$obj->text = $data["text"];
			$obj->title = $data["title"];
			
			$results[] = $obj;
		}

        return new JsonResponse($results);
	}
}