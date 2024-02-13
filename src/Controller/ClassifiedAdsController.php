<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\ClassifiedAds;
use App\Entity\Language;
use App\Entity\State;
use App\Entity\FileManagement;
use App\Form\Type\ClassifiedAdsType;

class ClassifiedAdsController extends AbstractController
{
    public function index(Request $request, EntityManagerInterface $em)
    {
    }
	
	// USER PARTICIPATION
    public function newAction(Request $request, EntityManagerInterface $em, Security $security, AuthorizationCheckerInterface $authorizationChecker)
    {
        $entity = new ClassifiedAds();
        $form = $this->createForm(ClassifiedAdsType::class, $entity, ['locale' => $request->getLocale()]);

        return $this->render('classifiedads/ClassifiedAds/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    public function create(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $entity  = new ClassifiedAds();
        $form = $this->createForm(ClassifiedAdsType::class, $entity, ['locale' => $request->getLocale()]);

        $form->handleRequest($request);
// dd($form);
		if ($form->isValid()) {
		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);
		$state = $em->getRepository(State::class)->findOneBy(['internationalName' => 'Waiting', 'language' => $language]);

		$entity->setState($state);
// dd($entity->getIllustration());
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

        return $this->render('classifiedads/ClassifiedAds/new.html.twig', [
            'entity' => $entity,
            'form'   => $form->createView()
        ]);
    }

    public function validate()
    {
		return $this->render('classifiedads/ClassifiedAds/validate.html.twig');
    }
}