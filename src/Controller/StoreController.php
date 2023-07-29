<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Stores\Store;
use App\Form\Type\StoreSearchType;
use Knp\Component\Pager\PaginatorInterface;

class StoreController extends AbstractController
{
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, $page)
    {
		$nbMessageByPage = 12;

		$form = $this->createForm(StoreSearchType::class);
		$form->handleRequest($request);
		$datas = null;

		if(!empty($d = $request->query->all()))
			$datas = $d;
		
		if ($form->isSubmitted() && $form->isValid() && isset($datas[$form->getName()]))
			$datas = $datas[$form->getName()];

		$query = $em->getRepository(Store::class)->getStores($datas, $nbMessageByPage, $page, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('store/Store/index.html.twig', ['pagination' => $pagination, 'form' => $form->createView()]);
    }

	public function showAction(EntityManagerInterface $em, $id, $title)
	{
		$entity = $em->getRepository(Store::class)->find($id);

		return $this->render('store/Store/show.html.twig', ['entity' => $entity]);
	}
	
	public function sliderAction(Request $request, EntityManagerInterface $em)
	{
		$entities = $em->getRepository(Store::class)->getSlider($request->getLocale());

		return $this->render("store/Widget/slider.html.twig", array(
			"entities" => $entities
		));
	}

    public function randomAction(Request $request, EntityManagerInterface $em)
    {
		$entity = $em->getRepository(Store::class)->getRandom($request->getLocale(), [Store::BOOK_CATEGORY]);
		
        return new Response($entity->getimageEmbeddedCode());
    }
}