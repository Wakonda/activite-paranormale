<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Licence;
use App\Entity\Language;

class LicenceController extends AbstractController
{
	#[Route('/showcolorbox', name: 'Licence_ShowColorbox')]
    public function showColorbox(Request $request, EntityManagerInterface $em)
    {
		$language = $em->getRepository(Language::class)->findOneBy(['abbreviation' => $request->getLocale()]);
		$entities = $em->getRepository(Licence::class)->findBy(['language' => $language]);

		if(!$request->isXmlHttpRequest())
			return $this->render('index/Licence/show.html.twig', ['entities' => $entities]);

		return $this->render('index/Licence/showColorbox.html.twig', ['entities' => $entities]);
    }

	#[Route('/showcolorboxbylicence/{id}', name: 'Licence_ShowColorboxByLicence')]
    public function showColorboxByLicence(Request $request, EntityManagerInterface $em, $id)
    {
		$entity = $em->getRepository(Licence::class)->find($id);

		if(!$request->isXmlHttpRequest())
			return $this->render('index/Licence/showByLicence.html.twig', ['entity' => $entity]);

        return $this->render('index/Licence/showColorboxByLicence.html.twig', ['entity' => $entity]);
    }
}