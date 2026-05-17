<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Region;
use App\Entity\Biography;
use App\Entity\MappedSuperclassBase;

#[Route('/region')]
class RegionController extends AbstractController
{
	#[Route('/{id}/{slug}', name: 'Region_Index')]
	public function index(EntityManagerInterface $em, Request $request, TranslatorInterface $translator, $id, $slug)
	{
		$region = $em->getRepository(Region::class)->find($id);

		$biographies = $em->getRepository(Biography::class)->getEntitiesByRegion($region);

		$locale =  $request?->getLocale() ?? 'fr'; 
		$entities = $em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		
		$res = [];

		foreach($entities as $className) {
			if(is_subclass_of($className, MappedSuperclassBase::class) && method_exists($em->getRepository($className), "getAllByRegion")) {
				$entities = $em->getRepository($className)->getAllByRegion($className, $locale);
				
				if(!empty($entities)) {
					$res[$translator->trans("index.className.".(new \ReflectionClass(new $className()))->getShortName(), [], 'validators')] = $entities;
				}
			}
		}
		
		ksort($res);

		return $this->render('index/Region/index.html.twig', [
			'entity' => $region,
			'biographies' => $biographies,
			'datas' => $res
		]);
	}
}