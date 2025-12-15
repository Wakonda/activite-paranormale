<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\User;
use App\Entity\Language;
use App\Entity\State;
use App\Form\Type\UserAdminType;
use App\Service\APDate;
use App\Service\ConstraintControllerValidator;

class AdminUserController extends AdminGenericController
{
	protected $entityName = 'User';
	protected $className = User::class;
	
	protected $countEntities = "countAdmin";
	protected $getDatatablesForIndexAdmin = "getDatatablesForIndexAdmin";
	
	protected $indexRoute = "User_Admin_Index"; 
	protected $showRoute = "apadminuser_show";
	protected $formName = 'ap_user_useradmintype';
	protected $illustrations = [["field" => "avatar", 'selectorFile' => 'photo_selector']];
	
	public function validationForm(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $form, $entityBindded, $entityOriginal)
	{
		$entityBindded->setUsernameCanonical();
		$entityBindded->setEmailCanonical();
		$ccv->fileConstraintValidator($form, $entityBindded, $entityOriginal, $this->illustrations);
	}

	public function postValidation($form, EntityManagerInterface $em, $entityBindded)
	{
	}

	#[Route('/admin/member', name: 'apadminuser')]
    public function index()
    {
		$twig = 'user/AdminUser/index.html.twig';
		return $this->indexGeneric($twig);
    }

	#[Route('/admin/member/datatables', name: 'User_Admin_IndexDatatables', methods: ['GET'])]
	public function indexDatatables(Request $request, TranslatorInterface $translator, EntityManagerInterface $em)
	{
		$informationArray = $this->indexDatatablesGeneric($request, $em);
		$output = $informationArray['output'];

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

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
			 <a href='".$this->generateUrl('apadminuser_show', ['id' => $entity->getId()])."'><i class='fas fa-book' aria-hidden='true'></i> ".$translator->trans('admin.general.Read', [], 'validators')."</a><br>
			 <a href='".$this->generateUrl('User_Admin_Edit', ['id' => $entity->getId()])."'><i class='fas fa-sync-alt' aria-hidden='true'></i> ".$translator->trans('admin.general.Update', [], 'validators')."</a><br>
			";
			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/admin/member/{id}/show', name: 'apadminuser_show')]
	public function show(EntityManagerInterface $em, $id)
    {
        $entity = $em->getRepository(User::class)->find($id);
		
        return $this->render('user/AdminUser/show.html.twig', [
			'entity' => $entity
		]);
    }

	#[Route('/admin/member/new', name: 'User_Admin_New')]
    public function newAction(Request $request, EntityManagerInterface $em)
    {
		$formType = UserAdminType::class;
		$entity = new User();

		$twig = 'user/AdminUser/new.html.twig';
		return $this->newGeneric($request, $em, $twig, $entity, $formType, ['action' => 'new', 'locale' => $request->getLocale()]);
    }

	#[Route('/admin/member/create', name: 'User_Admin_Create', methods: ['POST'])]
    public function createAction(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator)
    {
		$formType = UserAdminType::class;
		$entity = new User();

		$twig = 'user/AdminUser/new.html.twig';
		return $this->createGeneric($request, $em, $ccv, $translator, $twig, $entity, $formType, ['action' => 'new', 'locale' =>  $request->getLocale()]);
    }

	#[Route('/admin/member/{id}/edit', name: 'User_Admin_Edit')]
    public function edit(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(User::class)->find($id);
		$formType = UserAdminType::class;

		$twig = 'user/AdminUser/edit.html.twig';
		return $this->editGeneric($em, $id, $twig, $formType, ['action' => 'edit', 'locale' => $request->getLocale()]);
    }

	#[Route('/admin/member/{id}/update', name: 'User_Admin_Update', methods: ['POST'])]
	public function update(Request $request, EntityManagerInterface $em, ConstraintControllerValidator $ccv, TranslatorInterface $translator, $id)
    {
		$formType = UserAdminType::class;
		$twig = 'user/AdminUser/edit.html.twig';

		return $this->updateGeneric($request, $em, $ccv, $translator, $id, $twig, $formType, ['action' => 'edit', 'locale' => $request->getLocale()]);
    }

	#[Route('/list/member/{page}', name: 'apadminuser_listUser', defaults: ['page' => 1], requirements: ['page' => '\d+'])]
	public function userListing(EntityManagerInterface $em)
	{
		$users = $em->getRepository(User::class)->getMembersUser();
		
		return $this->render('user/AdminUser/userListing.html.twig', [
			'users' => $users
		]);
	}

	#[Route('/contribution_user/{id}/{bundleClassName}/{entityName}/{title}/{displayState}', name: 'APUserBunble_contributionuser', defaults: ['displayState' => 1])]
	public function contributionUser(EntityManagerInterface $em, $id, $bundleClassName, $displayState, $title, $entityName)
	{
		$user = $em->getRepository(User::class)->find($id);

		return $this->render('user/AdminUser/contribution_user.html.twig', ['id' => $id, 'bundleClassName' => $bundleClassName, 'user' => $user, 'displayState' => $displayState, "title" => $title, "entityName" => $entityName]);
	}

	#[Route('/contribution_user_datatables/{id}/{bundleClassName}/{displayState}', name: 'APUserBunble_contributionuserdatatables', defaults: ['displayState' => 1])]
	public function contributionUserDatatables(Request $request, EntityManagerInterface $em, APDate $date, $id, $bundleClassName, $displayState)
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

		$user = $em->getRepository(User::class)->find($id);

        $entities = $em->getRepository(User::class)->getUsersContribution($user, base64_decode($bundleClassName), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, false, $displayState);
		$iTotal = $em->getRepository(User::class)->getUsersContribution($user, base64_decode($bundleClassName), $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true, $displayState);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $language]);

		foreach($entities as $entity)
		{
			$row = [];
			
			if($entity->getState()->getDisplayState() == 1)
				$url = $this->generateUrl($entity->getShowRoute(), ['id' => $entity->getId(), 'title' => $entity->getTitle()]);
			else
				$url = $this->generateUrl($entity->getWaitingRoute(), ['id' => $entity->getId()]);
			
			$title = $entity->getTitle();
			$row[] = '<a href="'.$url.'">'.((!empty($title)) ? $title : "---").'</a>';
			$row[] = $entity->getState()->getTitle();
			$row[] = '<img src="'.$request->getBasePath().'/'.$entity->getLanguage()->getAssetImagePath().$entity->getLanguage()->getLogo().'" alt="'.$entity->getLanguage()->getAbbreviation().'">';

			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => $entity->getState()->getInternationalName(), 'language' => $language]);

			if(method_exists($entity, "getPublicationDate") and $entity->getState()->getDisplayState() == 1)
				$row[] = $date->doDate($language->getAbbreviation(), $entity->getPublicationDate());
			else
				$row[] = $date->doDate($language->getAbbreviation(), $entity->getWritingDate());
			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/contribution_user/{id}', name: 'APUserBunble_contributionusercomments')]
	public function contributionUserComments(TranslatorInterface $translator, EntityManagerInterface $em, $id)
	{
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

	#[Route('/contribution_user_datatables/{id}', name: 'APUserBunble_contributionusercommentsdatatables')]
	public function contributionUserCommentsDatatables(Request $request, EntityManagerInterface $em, APDate $date, TranslatorInterface $translator, $id)
	{
		$language = $request->getLocale();

		$className = $request->query->get("className");
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

		$user = $em->getRepository(User::class)->find($id);
		
        $entities = $em->getRepository(User::class)->getUsersCommentContribution($user, $className, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(User::class)->getUsersCommentContribution($user, $className, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = $entity->getMessageComment();
			$row[] = $date->doDateTime($language, $entity->getDateComment());
			$row[] = '<a href="'.$this->generateUrl($entity->getEntityLinked()->getShowRoute(), ["title" => $entity->getEntityLinked()->getTitle(), "id" => $entity->getEntityLinked()->getId()]).'">'.$translator->trans('user.contributionUserComments.Link', [], 'validators').'</a>';
			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/logout', name: 'Security_Logout')]
	#[Route('/user_logout', name: 'APUserBundle_logout')]
	public function logout()
	{	
		return $this->render('user/Security/logout.html.twig');		
	}

	#[Route('/admin/member/activate/{id}/{state}', name: 'User_Admin_Activate', methods: ['GET'])]
	public function activate(EntityManagerInterface $em, $id, $state)
	{
		$user = $em->getRepository(User::class)->find($id);

		$user->setEnabled($state);

		$em->persist($user);
		$em->flush();

		return $this->redirect($this->generateUrl("apadminuser_show", ['id' => $user->getId()]));
	}

	#[Route('/admin/member/remove/{id}', name: 'User_Admin_Remove', methods: ['GET'])]
	public function remove(EntityManagerInterface $em, TranslatorInterface $translator, $id)
	{
		$meta = $em->getMetadataFactory()->getAllMetadata();
		$res = [];
		foreach ($meta as $m) {
			$c = $m->getName();

			foreach($m->getAssociationMappings() as $field => $am)
				if($am["targetEntity"] == User::class)
					$res[] = $em->getRepository($c)->createQueryBuilder('c')->select("COUNT(c)")->where("c.$field = :id")->setParameter("id", $id)->getQuery()->getSingleScalarResult();
		}

		if(array_sum($res) > 0) {
			$this->addFlash('error', $translator->trans('user.admin.UnableToDelete', [], 'validators'));
		} else {
			$user = $em->getRepository(User::class)->find($id);
			$em->remove($user);
			$em->flush();
			
			$this->addFlash('success', $translator->trans('user.admin.DeleteWithSuccess', [], 'validators'));
		}

		return $this->redirect($this->generateUrl("apadminuser"));
	}
}