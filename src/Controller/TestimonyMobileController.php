<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Form\Type\TestimonyType;
use App\Entity\Testimony;
use App\Entity\TestimonyFileManagement;
use App\Entity\Licence;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\Theme;
use App\Entity\User;

require_once realpath(__DIR__."/../../vendor/mobiledetect/mobiledetectlib/Mobile_Detect.php");

class TestimonyMobileController extends AbstractController
{
    public function indexAction(Request $request, PaginatorInterface $paginator, $page, $theme)
    {
		$em = $this->getDoctrine()->getManager();
		$locale = $request->getLocale();

		$query = $em->getRepository(Testimony::class)->getEntitiesPagination($page, $theme, $locale);
		$themes = $em->getRepository(Theme::class)->getTheme($locale);

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		$mobileDetector = new \Mobile_Detect;
		
		if($mobileDetector->isMobile())
			$pagination->setPageRange(1);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('mobile/Testimony/index.html.twig', array(
			'pagination' => $pagination,
			'currentPage' => $page,
			'themes' => $themes
		));
    }

	public function selectThemeForIndexTestimonyAction(Request $request)
	{
		$themeId = $request->request->get('theme_news');

		$em = $this->getDoctrine()->getManager();
		$theme = $em->getRepository(Theme::class)->find($themeId);

		return new Response($this->generateUrl('ap_testimonymobile_index', array('page' => 1, 'theme' => $theme->getTitle())));
	}

	public function readAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Testimony::class)->find($id);
		$files = $em->getRepository(TestimonyFileManagement::class)->getAllFilesForTestimonyByIdClassName($entity->getId());
		
		return $this->render('mobile/Testimony/read.html.twig', array(
			'entity' => $entity,
			'files' => $files
		));
	}

	public function newAction(Request $request)
	{
		$entity = new Testimony();
		$entity->setLicence($this->getDoctrine()->getManager()->getRepository(Licence::class)->getOneLicenceByLanguageAndInternationalName($request->getLocale(), "CC-BY-NC-ND"));
		$form = $this->createForm(TestimonyType::class, $entity, array('locale' => $request->getLocale()));

		return $this->render('mobile/Testimony/new.html.twig', array('form' => $form->createView()));
	}

	public function createAction(Request $request, TranslatorInterface $translator)
	{
		$entity  = new Testimony();
		$form = $this->createForm(TestimonyType::class, $entity, array('locale' => $request->getLocale()));
		
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid())
		{
			$em = $this->getDoctrine()->getManager();
			$language = $em->getRepository(Language::class)->findOneBy(array('abbreviation' => $request->getLocale()));
			$state = $em->getRepository(State::class)->findOneBy(array('internationalName' => 'Waiting', 'language' => $language));

			$entity->setState($state);
			$entity->setLanguage($language);
			$anonymousUser = $em->getRepository(User::class)->findOneBy(array('username' => 'Anonymous'));
			$entity->setAuthor($anonymousUser);
			$entity->setIsAnonymous(1);
			
			$em->persist($entity);
			$em->flush();

			if($form->get('addFile')->isClicked())
				return $this->redirect($this->generateUrl('ap_testimonymobile_addfile', array('id' => $entity->getId())));

			$this->addFlash('success', $translator->trans('testimony.validate.ThankForYourParticipationText', array(), 'validators'));

			return $this->redirect($this->generateUrl('ap_newsmobile_index', array('page' => 1)));
		}
		
		return $this->render('mobile/Testimony/new.html.twig', array('form' => $form->createView()));
	}

	public function addFileAction($id)
	{
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Testimony::class)->find($id);
		return $this->render('mobile/Testimony/addFile.html.twig', array('entity' => $entity));
	}

	public function validateFileAction(TranslatorInterface $translator)
	{
		$this->addFlash('success', $translator->trans('testimony.validate.ThankForYourParticipationText', array(), 'validators'));
			
		return $this->redirect($this->generateUrl('ap_newsmobile_index', array('page' => 1)));
	}
}