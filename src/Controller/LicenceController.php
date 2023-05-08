<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Licence;
use App\Entity\Language;

class LicenceController extends AbstractController
{
    public function showColorboxAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);

		$entities = $em->getRepository(Licence::class)->findBy(['language' => $language]);

        return $this->render('index/Licence/showColorbox.html.twig', ['entities' => $entities]);
    }

    public function showColorboxByLicenceAction($id)
    {
		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository(Licence::class)->find($id);

        return $this->render('index/Licence/showColorboxByLicence.html.twig', ['entity' => $entity]);
    }
}