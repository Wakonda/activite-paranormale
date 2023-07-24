<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\User;
use App\Entity\Language;
use App\Entity\State;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;

class AdminUserController extends AdminGenericController
{
	protected $entityName = 'User';
	protected $className = User::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "User_Admin_Index"; 
	protected $showRoute = "User_Admin_Show";
	
	public function validationForm(Request $request, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
	}

	public function postValidationAction($form, $entityBindded)
	{
	}
	
    public function indexAction($page)
    {
		$twig = 'user/AdminUser/index.html.twig';
		return $this->indexGenericAction($twig);
    }

	public function indexDatatablesAction(Request $request, TranslatorInterface $translator)
	{
		$em = $this->getDoctrine()->getManager();
		$informationArray = $this->indexDatatablesGenericAction($request);
		$output = $informationArray['output'];

		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));

		foreach($informationArray['entities'] as $entity)
		{
			$row = [];

			$row[] = $entity->getId();
			$row[] = $entity->getUsername();
			$row[] = $entity->getEmail();
			$row[] = implode("<br>", $entity->getRoles());

			if($entity->isEnabled())
				$state = '<span class="text-success"><i class="fas fa-check" aria-hidden="true"></i></span>';
			else
				$state = '<span class="text-danger"><i class="fas fa-times" aria-hidden="true"></i></span>';
			$row[] = $state;
			
			$row[] = "
			 <a href='".$this->generateUrl('apadminuser_show', array('id' => $entity->getId()))."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			";
			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function showAction($id)
    {
		$em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository(User::class)->find($id);
		
        return $this->render('user/AdminUser/show.html.twig', array(
			'entity' => $entity
		));
    }
	
	public function userListingAction()
	{
		$em = $this->getDoctrine()->getManager();
		
		$users = $em->getRepository(User::class)->getMembersUser();
		
		return $this->render('user/AdminUser/userListing.html.twig', array(
			'users' => $users
		));	
	}

	public function contributionUserAction($id, $bundleClassName, $displayState, $title, $entityName)
	{
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository(User::class)->find($id);

		return $this->render('user/AdminUser/contribution_user.html.twig', array('id' => $id, 'bundleClassName' => $bundleClassName, 'user' => $user, 'displayState' => $displayState, "title" => $title, "entityName" => $entityName));
	}
	
	public function contributionUserDatatablesAction(Request $request, APDate $date, $id, $bundleClassName, $displayState)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $request->getLocale();

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

		$user = $em->getRepository(User::class)->find($id);

        $entities = $em->getRepository(User::class)->getUsersContribution($user, base64_decode($bundleClassName), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, false, $displayState);
		$iTotal = $em->getRepository(User::class)->getUsersContribution($user, base64_decode($bundleClassName), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true, $displayState);

		$output = array(
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		);
		
		$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $language));

		foreach($entities as $entity)
		{
			$row = [];
			
			if($entity->getState()->getDisplayState() == 1)
				$url = $this->generateUrl($entity->getShowRoute(), array('id' => $entity->getId(), 'title' => $entity->getTitle()));
			else
				$url = $this->generateUrl($entity->getWaitingRoute(), array('id' => $entity->getId()));
			
			$title = $entity->getTitle();
			$row[] = '<a href="'.$url.'">'.((!empty($title)) ? $title : "---").'</a>';
			$row[] = $entity->getState()->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="'.$entity->getLanguage()->getAbbreviation().'">';

			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => $entity->getState()->getInternationalName(), 'language' => $language));

			if(method_exists($entity, "getPublicationDate") and $entity->getState()->getDisplayState() == 1)
				$row[] = $date->doDate($language->getAbbreviation(), $entity->getPublicationDate());
			else
				$row[] = $date->doDate($language->getAbbreviation(), $entity->getWritingDate());
			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}
	
	public function contributionUserCommentsAction(TranslatorInterface $translator, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository(User::class)->find($id);
		
		
		$entities = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		
		$classArray = [];

		foreach($entities as $entity) {
			if(is_subclass_of($entity, \App\Entity\Comment::class)) {
				$cn = (new $entity())->getMainEntityClassName();
				$classArray[$entity] = $translator->trans(("index.className.".((new \ReflectionClass($cn))->getShortName())), [], 'validators');
			}
		}

		asort($classArray);

		return $this->render('user/AdminUser/contribution_user_comments.html.twig', ['id' => $id, 'user' => $user, 'classArray' => $classArray]);
	}
	
	public function contributionUserCommentsDatatablesAction(Request $request, APDate $date, TranslatorInterface $translator, $id)
	{
		$em = $this->getDoctrine()->getManager();
		$language = $request->getLocale();

		$className = $request->query->get("className");
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

		$user = $em->getRepository(User::class)->find($id);
		
        $entities = $em->getRepository(User::class)->getUsersCommentContribution($user, $className, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(User::class)->getUsersCommentContribution($user, $className, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"sEcho" => $request->query->get('sEcho'),
			"iTotalRecords" => $iTotal,
			"iTotalDisplayRecords" => $iTotal,
			"aaData" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getMessageComment();
			$row[] = $date->doDateTime($language, $entity->getDateComment());
			$row[] = '<a href="'.$this->generateUrl($entity->getEntityLinked()->getShowRoute(), ["title" => $entity->getEntityLinked()->getTitle(), "id" => $entity->getEntityLinked()->getId()]).'">'.$translator->trans('user.contributionUserComments.Link', [], 'validators').'</a>';
			$output['aaData'][] = $row;
		}

		return new JsonResponse($output);
	}

	public function logoutAction()
	{	
		return $this->render('user/Security/logout.html.twig');		
	}
	
	public function activateAction($id, $state)
	{
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository(User::class)->find($id);
		
		$user->setEnabled($state);

		$em->persist($user);
		$em->flush();

		return $this->redirect($this->generateUrl("apadminuser_show", ['id' => $user->getId()]));
	}
}