<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Region;
use App\Entity\Biography;

#[Route('/region')]
class RegionController extends AbstractController
{
	#[Route('/{id}/{slug}', name: 'Region_Index')]
	public function index(EntityManagerInterface $em, $id, $slug)
	{
		$entity = $em->getRepository(Region::class)->find($id);

		$biographies = $em->getRepository(Biography::class)->getEntitiesByRegion($entity);

		return $this->render('index/Region/index.html.twig', [
			'entity' => $entity,
			'biographies' => $biographies
		]);
	}
}