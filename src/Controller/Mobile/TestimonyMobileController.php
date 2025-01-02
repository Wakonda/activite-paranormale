<?php

namespace App\Controller\Mobile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;

use App\Form\Type\TestimonyType;
use App\Entity\Testimony;
use App\Entity\TestimonyFileManagement;
use App\Entity\Licence;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\Theme;
use App\Entity\User;
use App\Service\FunctionsLibrary;

require_once realpath(__DIR__."/../../../vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php");

class TestimonyMobileController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, FunctionsLibrary $functionsLibrary, $page, $theme)
    {
		$locale = $request->getLocale();

		$query = $em->getRepository(Testimony::class)->getEntitiesPagination($page, $theme, $locale);
		$themes = $em->getRepository(Theme::class)->getTheme($locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		if((new \Mobile_Detect)->isMobile() or $functionsLibrary->isApplication())
			$pagination->setPageRange(3);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('mobile/Testimony/index.html.twig', [
			'pagination' => $pagination,
			'currentPage' => $page,
			'themes' => $themes
		]);
    }

	public function selectThemeForIndexTestimonyAction(Request $request, EntityManagerInterface $em)
	{
		$themeId = $request->request->get('theme_news');
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_testimonymobile_index', ['page' => 1, 'theme' => $theme->getTitle()]));
	}

	public function readAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Testimony::class)->find($id);
		$files = $em->getRepository(TestimonyFileManagement::class)->getAllFilesByIdClassName($entity->getId());
		
		return $this->render('mobile/Testimony/read.html.twig', [
			'entity' => $entity,
			'files' => $files
		]);
	}

	public function newAction(Request $request, EntityManagerInterface $em, Security $security)
	{
		$entity = new Testimony();
		$entity->setLicence($em->getRepository(Licence::class)->getOneLicenceByLanguageAndInternationalName($request->getLocale(), "CC-BY-NC-ND 3.0"));

		$form = $this->createForm(TestimonyType::class, $entity, ['locale' => $request->getLocale()]);
		
		return $this->render('mobile/Testimony/new.html.twig', ['form' => $form->createView()]);
	}

	public function createAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, Security $security)
	{
		$entity  = new Testimony();
		$user = $security->getUser();
		$form = $this->createForm(TestimonyType::class, $entity, ['locale' => $request->getLocale()]);

		$form->handleRequest($request);
	
		if ($form->isSubmitted() && $form->isValid())
		{
			$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));

			$entity->setState($state);
			$entity->setLanguage($language);

			if(is_object($user) and !$entity->getIsAnonymous())
				$entity->setAuthor($user);
			else
			{
				$anonymousUser = $em->getRepository(User::class)->findOneBy(array('username' => 'Anonymous'));
				$entity->setAuthor($anonymousUser);
				$entity->setIsAnonymous(1);
			}
			
			$em->persist($entity);
			$em->flush();

			if($form->get('addFile')->isClicked())
				return $this->redirect($this->generateUrl('ap_testimonymobile_addfile', array('id' => $entity->getId())));

			$this->addFlash('success', $translator->trans('testimony.validate.ThankForYourParticipationText', [], 'validators'));

			return $this->redirect($this->generateUrl('ap_newsmobile_index', array('page' => 1)));
		}
		
		return $this->render('mobile/Testimony/new.html.twig', array('form' => $form->createView()));
	}

	public function addFileAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Testimony::class)->find($id);
		return $this->render('mobile/Testimony/addFile.html.twig', array('entity' => $entity));
	}

	public function validateFileAction(TranslatorInterface $translator)
	{
		$this->addFlash('success', $translator->trans('testimony.validate.ThankForYourParticipationText', [], 'validators'));
			
		return $this->redirect($this->generateUrl('ap_newsmobile_index', ['page' => 1]));
	}
}