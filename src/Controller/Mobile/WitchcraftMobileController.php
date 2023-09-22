<?php

namespace App\Controller\Mobile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Grimoire;
use App\Entity\SurThemeGrimoire;
use App\Entity\User;
use App\Entity\Language;
use App\Entity\State;
use App\Form\Type\GrimoireUserParticipationType;

class WitchcraftMobileController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page, $theme)
    {
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
		
		$language = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $locale]);
		$surThemeGrimoires = $em->getRepository(SurThemeGrimoire::class)->findBy(["language" => $language]);
		$themeArray = [];
		
		foreach($surThemeGrimoires as $menuGrimoire) {
			if(empty($menuGrimoire->getParentTheme()))
				$themeArray[$menuGrimoire->getTitle()] = [];
		}

		foreach($themeArray as $key => $value) {
			$subArray = [];
			
			foreach($surThemeGrimoires as $surThemeGrimoire) {
				if(!empty($surThemeGrimoire->getParentTheme()) and $surThemeGrimoire->getParentTheme()->getTitle() == $key) {
					$subArray[$surThemeGrimoire->getId()] = $surThemeGrimoire->getTitle();
				}
			}
			$themeArray[$key] = $subArray;
		}

		return $this->render('mobile/Witchcraft/index.html.twig', [
			'pagination' => $pagination,
			'currentPage' => $page,
			'themeArray' => $themeArray
		]);
    }

	public function selectThemeForIndexWitchcraftAction(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_news');
		$theme = $em->getRepository(SurThemeGrimoire::class)->find($themeId);

		return new Response($this->generateUrl('ap_witchcraftmobile_index', ['page' => 1, 'theme' => $theme->getTitle()]));
	}

	public function readAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Grimoire::class)->find($id);
		
		return $this->render('mobile/Witchcraft/read.html.twig', [
			'entity' => $entity
		]);
	}

	public function newAction(Request $request)
	{
        $entity = new Grimoire();
		
		$user = $this->getUser();
        $form = $this->createForm(GrimoireUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user]);

		return $this->render('mobile/Witchcraft/new.html.twig', ['form' => $form->createView()]);
	}

	public function createAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
	{
		$entity  = new Grimoire();
		$form = $this->createForm(GrimoireUserParticipationType::class, $entity, ["language" => $request->getLocale(), "user" => $user]);
		
		$form->handleRequest($request);

		if(is_object($user))
		{
			if($entity->getIsAnonymous() == 1)
			{
				if($form->get('validate')->isClicked())
					$user = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);
				
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
			$user = $em->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);
			$entity->setAuthor($user);
			$entity->setIsAnonymous(0);
		}
		
		if ($form->isSubmitted() && $form->isValid())
		{
			$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);

			$entity->setState($state);
			$entity->setLanguage($language);
			
			$em->persist($entity);
			$em->flush();

			$this->addFlash('success', $translator->trans('witchcraft.validate.ThankForYourParticipationText', [], 'validators'));

			return $this->redirect($this->generateUrl('ap_witchcraftmobile_index'));
		}

		return $this->render('mobile/Witchcraft/new.html.twig', ['form' => $form->createView()]);
	}
}