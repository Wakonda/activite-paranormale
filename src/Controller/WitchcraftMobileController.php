<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\Grimoire;
use App\Entity\MenuGrimoire;
use App\Entity\SurThemeGrimoire;
use App\Entity\User;
use App\Entity\Language;
use App\Entity\State;
use App\Form\Type\GrimoireUserParticipationType;

class WitchcraftMobileController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page, $theme)
    {
		$em = $this->getDoctrine()->getManager();
		$locale = $request->getLocale();

		$query = $em->getRepository(Grimoire::class)->getEntitiesPagination($page, $theme, $locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		$mobileDetector = new \Mobile_Detect;
		
		if($mobileDetector->isMobile())
			$pagination->setPageRange(1);

		$pagination->setCustomParameters(['align' => 'center']);
		
		$menuGrimoires = $em->getRepository(MenuGrimoire::class)->getSurThemeGrimoire($locale);
		$surThemeGrimoires = $em->getRepository(SurThemeGrimoire::class)->getSurThemeByLanguage($locale);
		$themeArray = array();
		
		foreach($menuGrimoires as $menuGrimoire) {
			$themeArray[$menuGrimoire->getTitle()] = array();
		}
		
		foreach($themeArray as $key => $value) {
			$subArray = array();
			
			foreach($surThemeGrimoires as $surThemeGrimoire) {
				if($surThemeGrimoire->getMenuGrimoire()->getTitle() == $key) {
					$subArray[$surThemeGrimoire->getId()] = $surThemeGrimoire->getTitle();
				}
			}
			$themeArray[$key] = $subArray;
		}

		return $this->render('mobile/Witchcraft/index.html.twig', array(
			'pagination' => $pagination,
			'currentPage' => $page,
			'themeArray' => $themeArray
		));
    }

	public function selectThemeForIndexWitchcraftAction(Request $request)
	{
		$themeId = $request->request->get('theme_news');

		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(SurThemeGrimoire::class)->find($themeId);

		return new Response($this->generateUrl('ap_witchcraftmobile_index', array('page' => 1, 'theme' => $theme->getTitle())));
	}

	public function readAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Grimoire::class)->find($id);
		
		return $this->render('mobile/Witchcraft/read.html.twig', array(
			'entity' => $entity
		));
	}

	public function newAction(Request $request)
	{
		$securityUser = $this->container->get('security.authorization_checker');
        $entity = new Grimoire();
		
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(GrimoireUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);

		return $this->render('mobile/Witchcraft/new.html.twig', array('form' => $form->createView()));
	}

	public function createAction(Request $request, TranslatorInterface $translator)
	{
		$user = $this->container->get('security.token_storage')->getToken()->getUser();
		$securityUser = $this->container->get('security.authorization_checker');
		$entity  = new Grimoire();
		$form = $this->createForm(GrimoireUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user, "securityUser" => $securityUser]);
		
		$form->handleRequest($request);
		$em = $this->getDoctrine()->getManager();

		if(is_object($user))
		{
			if($entity->getIsAnonymous() == 1)
			{
				if($form->get('validate')->isClicked())
					$user = $em->getRepository(User::class)->findOneBy(array('username' => 'Anonymous'));
				
				$entity->setAuthor($user);
				$entity->setPseudoUsed("Anonymous");
			}
			else
			{
				$entity->setAuthor($user);
				$entity->setPseudoUsed($user->getUsername());
			}
		}
		else
		{
			$user = $em->getRepository(User::class)->findOneBy(array('username' => 'Anonymous'));
			$entity->setAuthor($user);
			$entity->setIsAnonymous(0);
		}
		
		if ($form->isSubmitted() && $form->isValid())
		{
			$em = $this->getDoctrine()->getManager();
			$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));

			$entity->setState($state);
			$entity->setLanguage($language);
			
			$em->persist($entity);
			$em->flush();

			$this->addFlash('success', $translator->trans('witchcraft.validate.ThankForYourParticipationText', array(), 'validators'));
			
			return $this->redirect($this->generateUrl('ap_witchcraftmobile_index'));
		}
		
		return $this->render('mobile/Witchcraft/new.html.twig', array('form' => $form->createView()));
	}
}