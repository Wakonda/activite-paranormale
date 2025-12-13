<?php

namespace App\Controller\Mobile;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Stores\Store;
use App\Form\Type\StoreSearchType;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\FunctionsLibrary;
use Detection\MobileDetect;

class StoreMobileController extends AbstractController
{
    #[Route('/mobile/store/{page}', name: 'ap_storemobile_index', defaults: ['page' => 1], requirements: ['page' => '\d+'])]
    public function indexAction(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, FunctionsLibrary $functionsLibrary, $page)
    {
		$nbMessageByPage = 12;

		$form = $this->createForm(StoreSearchType::class);
		$form->handleRequest($request);
		$datas = null;

		if(!empty($d = $request->query->all()))
			$datas = $d;
		
		if ($form->isSubmitted() && $form->isValid() && isset($datas[$form->getName()]))
			$datas = $datas[$form->getName()];

		if($request->query->has("reset"))
			$datas = [];

		$query = $em->getRepository(Store::class)->getStores($datas, $nbMessageByPage, $page, $request->getLocale());

		$pagination = $paginator->paginate(
			$query, /* query NOT result */
			$page, /*page number*/
			10 /*limit per page*/
		);

		if((new MobileDetect())->isMobile() or $functionsLibrary->isApplication())
			$pagination->setPageRange(3);

		$pagination->setCustomParameters(['align' => 'center']);

		return $this->render('mobile/Store/index.html.twig', ['pagination' => $pagination, 'form' => $form->createView()]);
    }

    #[Route('/mobile/store/read/{id}', name: 'ap_storemobile_read', requirements: ['id' => '\d+'])]
	public function showAction(EntityManagerInterface $em, $id)
	{
		$entity = $em->getRepository(Store::class)->find($id);

		return $this->render('mobile/Store/read.html.twig', ['entity' => $entity]);
	}
}