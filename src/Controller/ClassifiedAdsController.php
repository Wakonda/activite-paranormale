<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Knp\Component\Pager\PaginatorInterface;

use App\Entity\ClassifiedAds;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\FileManagement;
use App\Entity\ClassifiedAdsCategory;
use App\Entity\Region;
use App\Form\Type\ClassifiedAdsType;
use App\Form\Type\ClassifiedAdsSearchType;

class ClassifiedAdsController extends AbstractController
{
	#[Route('/classifiedads/{page}/{idCategory}', name: 'ClassifiedAds_Index', defaults: ['page' => 1, 'idCategory' => null], requirements: ['page' => "\d+"])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page, $idCategory)
    {
		$language = $em->getRepository(Language::class)->findOneBy(["abbreviation" => $request->getLocale()]);
		$counters = $em->getRepository(ClassifiedAds::class)->countByCategory($request->getLocale());

		$datas = [];

		if(!empty($idCategory))
			$datas["category"] = $em->getRepository(ClassifiedAdsCategory::class)->find($idCategory);

		if($request->query->has("category_title"))
			$datas["category"] = $em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $request->query->get("category_title"), "language" => $language]);

		$form = $this->createForm(ClassifiedAdsSearchType::class, $datas, ["locale" => $request->getLocale()]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
			$datas = $form->getData();
		
		if(!empty($request->query->has("location_raw"))) {
			$region = $em->getRepository(Region::class)->findOneBy(["title" => $request->query->get("location_raw")]);
			$datas["location_raw"] = (!empty($region) ? $region->getTitle() : $request->query->get("location_raw"));
			$datas["region"] = (!empty($region) ? $region->getInternationalName() : null);
		} else {
			$region = $em->getRepository(Region::class)->findOneBy(["title" => $form->get("location_raw")->getData()]);
			$datas["location_raw"] = (!empty($region) ? $region->getTitle() : $form->get("location_raw")->getData());
			$datas["region"] = (!empty($region) ? $region->getInternationalName() : null);
		}

		if($request->query->has("reset"))
			$datas = [];

		$query = $em->getRepository(ClassifiedAds::class)->getClassifiedAds($datas, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			12 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);
		$form = $this->createForm(ClassifiedAdsSearchType::class, $datas, ["locale" => $request->getLocale()]);

		return $this->render('classifiedAds/ClassifiedAds/index.html.twig', ['pagination' => $pagination, "form" => $form->createView(), "counters" => $counters]);
    }

	#[Route('/classifiedads/read/{id}/{title_slug}', name: 'ClassifiedAds_Read', defaults: ['title_slug' => null], requirements: ['id' => "\d+"])]
	public function read(EntityManagerInterface $em, $id, $title_slug) {
		$entity = $em->getRepository(ClassifiedAds::class)->find($id);
		
		return $this->render("classifiedAds/ClassifiedAds/read.html.twig", ["entity" => $entity]);
	}

	/* FONCTION DE COMPTAGE */
	public function countByLanguage(EntityManagerInterface $em, Request $request)
	{
		return new Response($em->getRepository(ClassifiedAds::class)->countByLanguage($request->getLocale()));
	}

	#[Route('/classifiedads/markas/{id}', name: 'ClassifiedAds_MarkAs', requirements: ['id' => "\d+"])]
	public function markAs(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $id) {
		$entity = $em->getRepository(ClassifiedAds::class)->find($id);

		$entity->setMarkAs($request->query->get("mark_as"));
		$em->persist($entity);
		$em->flush();

		$this->addFlash('success', $translator->trans('classifiedAds.read.MarkAsSuccess', [], 'validators'));
		
		return $this->redirect($this->generateUrl("ClassifiedAds_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug()]));
	}

	// USER PARTICIPATION
	#[Route('/classifiedads/new', name: 'ClassifiedAds_New')]
    public function newAction(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, AuthorizationCheckerInterface $authorizationChecker)
    {
        $entity = new ClassifiedAds();
        $form = $this->createForm(ClassifiedAdsType::class, $entity, ['locale' => $request->getLocale()]);

        return $this->render('classifiedAds/ClassifiedAds/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	#[Route('/classifiedads/create', name: 'ClassifiedAds_Create')]
    public function create(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $entity  = new ClassifiedAds();
        $form = $this->createForm(ClassifiedAdsType::class, $entity, ['locale' => $request->getLocale()]);

        $form->handleRequest($request);

		if ($form->isValid()) {
			$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);
			$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);
			
			$entity->setAuthor($this->getUser());

			$entity->setState($state);
			$entity->setLanguage($language);
			
			if(empty($entity->getPrice()))
				$entity->setCurrencyPrice(null);

			if(is_object($ci = $entity->getIllustration())) {
				$titleFile = uniqid()."_".$ci->getClientOriginalName();
				$illustration = new FileManagement();
				$illustration->setTitleFile($titleFile);
				$illustration->setRealNameFile($titleFile);
				$illustration->setExtensionFile(pathinfo($ci->getClientOriginalName(), PATHINFO_EXTENSION));

				$ci->move($entity->getTmpUploadRootDir(), $titleFile);

				$entity->setIllustration($illustration);
			}

            $em->persist($entity);
            $em->flush();

			return $this->redirect($this->generateUrl("ClassifiedAds_Validate"));
        }

        return $this->render('classifiedAds/ClassifiedAds/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

	#[Route('/classifiedads/validate', name: 'ClassifiedAds_Validate')]
    public function validate()
    {
		return $this->render('classifiedAds/ClassifiedAds/validate.html.twig');
    }

	#[Route('/classifiedads/state/{id}/{state}', name: 'ClassifiedAds_State')]
    public function state(Request $request, EntityManagerInterface $em, $id, $state)
    {
		$entity = $em->getRepository(ClassifiedAds::class)->find($id);
		
		if($entity->getAuthor()->getId() != $this->getUser()->getId())
			throw new AccessDeniedHttpException();
		
		if($state == 1) {
			$state = $em->getRepository(State::class)->findOneBy(["language" => $entity->getLanguage(), "internationalName" => State::$waiting]);
			$entity->setState($state);
		} else {
			$state = $em->getRepository(State::class)->findOneBy(["language" => $entity->getLanguage(), "internationalName" => State::$draft]);
			$entity->setState($state);
		}

		$em->persist($entity);
		$em->flush();
		
		return $this->redirect($this->generateUrl("ClassifiedAds_Read", ["id" => $entity->getId(), "title_slug" => $entity->getUrlSlug()]));
    }

	public function indexOsClass(Request $request, EntityManagerInterface $em) {
		return $this->render('classifiedAds/ClassifiedAds/indexOsClass.html.twig');
	}
}