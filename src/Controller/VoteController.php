<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Vote;
use App\Entity\NewsVote;
use App\Entity\VideoVote;
use App\Entity\GrimoireVote;
use App\Entity\TestimonyVote;
use App\Entity\PhotoVote;
use App\Entity\BookVote;
use App\Entity\WitchcraftToolVote;
use App\Entity\EventMessageVote;
use App\Entity\MovieVote;
use App\Entity\TelevisionSerieVote;
use App\Form\Type\VoteType;
use App\Entity\ClassifiedAdsVote;

class VoteController extends AbstractController
{
    private function getNewEntity($em, $className, $idClassName)
	{
		switch($className)
		{
			case "News":
				$entity = new NewsVote();
				$className = NewsVote::class;
				break;
			case "Video":
				$entity = new VideoVote();
				$className = VideoVote::class;
				break;
			case "Photo":
				$entity = new PhotoVote();
				$className = PhotoVote::class;
				break;
			case "Grimoire":
				$entity = new GrimoireVote();
				$className = GrimoireVote::class;
				break;
			case "Testimony":
				$entity = new TestimonyVote();
				$className = TestimonyVote::class;
				break;
			case "Book":
				$entity = new BookVote();
				$className = BookVote::class;
				break;
			case "WitchcraftTool":
				$entity = new WitchcraftToolVote();
				$className = WitchcraftToolVote::class;
				break;
			case "EventMessage":
				$entity = new EventMessageVote();
				$className = EventMessageVote::class;
				break;
			case "Movie":
				$entity = new MovieVote();
				$className = MovieVote::class;
				break;
			case "TelevisionSerie":
				$entity = new TelevisionSerieVote();
				$className = TelevisionSerieVote::class;
				break;
			case "ClassifiedAds":
				$entity = new ClassifiedAdsVote();
				$className = ClassifiedAdsVote::class;
				break;
		}

		$entity->setEntity($em->getRepository($entity->getMainEntityClassName())->find($idClassName));

		return [$entity, $className];
	}

	#[Route('/vote/{className}/{idClassName}', name: 'Vote_Index')]
    public function indexAction(Request $request, EntityManagerInterface $em, $className, $idClassName)
    {
		list($entity, $classNameVote) = $this->getNewEntity($em, $className, $idClassName);

		$averageVote = $em->getRepository($classNameVote)->averageVote($className, $idClassName);

		if($averageVote == null)
			$averageVote = null;

		$form = $this->createForm(VoteType::class, $entity, ["averageVote" => $averageVote]);
		$countVoteByClassName = $em->getRepository($classNameVote)->countVoteByClassName($classNameVote, $idClassName);
		$favorite = $em->getRepository(Vote::class)->findOneBy(["author" => $this->getUser(), "idClassVote" => $idClassName, "classNameVote" => $className]);

        return $this->render('vote/Vote/index.html.twig', [
			'className' => $className,
			'idClassName' => $idClassName,
			'countVoteByClassName' => $countVoteByClassName,
			'form' => $form->createView(),
			'favoriteEntity' => $favorite
		]);
    }

	#[Route('/vote/edit/{idClassName}/{className}', name: 'Vote_Edit')]
	public function editAction(Request $request, EntityManagerInterface $em, $className, $idClassName)
    {
		list($entity, $classNameVote) = $this->getNewEntity($em, $className, $idClassName);

		if(!$request->isXmlHttpRequest())
			throw $this->createNotFoundException('Unable to find this page.');

		$form = $this->createForm(VoteType::class, $entity);
		$form->handleRequest($request);

		if($request->isXmlHttpRequest())
		{
			$entity->setClassNameVote($className);
			$entity->setIdClassVote($idClassName);
			$entity->setAuthor($this->getUser());

			$em->persist($entity);
			$em->flush();
		}

		$averageVote = $em->getRepository($classNameVote)->averageVote($classNameVote, $idClassName);
		$countVoteByClassName = $em->getRepository($classNameVote)->countVoteByClassName($classNameVote, $idClassName);

		return new JsonResponse(['averageVote' => round($averageVote, 1), 'countVoteByClassName' => $countVoteByClassName]);
    }

	#[Route('/vote/list/user/{authorId}', name: 'Vote_ListVoteBtUser')]
	public function listVoteByUser($authorId) {
		return $this->render("vote/Vote/index_user.html.twig");
	}

	#[Route('/listVoteByUserDatatables/{authorId}', name: 'Vote_ListVoteByUserDatatables')]
	public function listVoteByUserDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $authorId) {
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = !empty($search = $request->query->all('search')) ? $search["value"] : [];

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++) {
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Vote::class)->getDatatablesForIndex($authorId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Vote::class)->getDatatablesForIndex($authorId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$color = match(intval(floor($entity->getValueVote()))) {
				0, 1 => "danger",
				2 => "warning",
				3 => "info",
				4 => "primary",
				5 => "success"
			};
			$row = [];
			$row[] = ((method_exists($entity->getEntity(), "getArchive") and $entity->getEntity()->getArchive()) ? '<i class="fas fa-key text-warning"></i>' : '').$translator->trans('index.className.'.$entity->getEntity()->getRealClass(), [], 'validators');
			$row[] = '<a href="'.$this->generateUrl($entity->getEntity()->getShowRoute(), ["title" => $entity->getEntity()->getTitle(), "id" => $entity->getEntity()->getId()]).'">'.$entity->getEntity()->getTitle()."</a>";
			$row[] = "<i class='fa-solid fa-star text-$color'></i> ".$entity->getValueVote();

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/favorite/list/user/{authorId}', name: 'Vote_ListFavoriteByUser')]
	public function listFavoriteByUser($authorId) {
		if(!empty($user = $this->getUser()) and $user->getId() != $authorId)
			throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('You have not access to this section.');

		return $this->render("vote/Vote/index_favorite_user.html.twig");
	}

	#[Route('/listFavoriteByUserDatatables/{authorId}', name: 'Vote_ListFavoriteByUserDatatables')]
	public function listFavoriteByUserDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $authorId) {
		if(!empty($user = $this->getUser()) and $user->getId() != $authorId)
			throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('You have not access to this section.');

		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		$sSearch = !empty($search = $request->query->all('search')) ? $search["value"] : [];

		$sortByColumn = [];
		$sortDirColumn = [];
			
		for($i=0 ; $i<intval($order = $request->query->all('order')); $i++) {
			$sortByColumn[] = $order[$i]['column'];
			$sortDirColumn[] = $order[$i]['dir'];
		}

        $entities = $em->getRepository(Vote::class)->getDatatablesFavoriteForIndex($authorId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch);
		$iTotal = $em->getRepository(Vote::class)->getDatatablesFavoriteForIndex($authorId, $iDisplayStart, $iDisplayLength, $sortByColumn, $sortDirColumn, $sSearch, true);

		$output = [
			"recordsTotal" => $iTotal,
			"recordsFiltered" => $iTotal,
			"data" => []
		];

		foreach($entities as $entity)
		{
			$row = [];
			$row[] = ((method_exists($entity->getEntity(), "getArchive") and $entity->getEntity()->getArchive()) ? '<i class="fas fa-key text-warning"></i>' : '').$translator->trans('index.className.'.$entity->getEntity()->getRealClass(), [], 'validators');
			$row[] = '<a href="'.$this->generateUrl($entity->getEntity()->getShowRoute(), ["title" => $entity->getEntity()->getTitle(), "id" => $entity->getEntity()->getId()]).'">'.$entity->getEntity()->getTitle()."</a>";

			$output['data'][] = $row;
		}

		return new JsonResponse($output);
	}

	#[Route('/favorite/post/user/{idClassName}/{className}', name: 'Vote_PostFavorite')]
	public function postFavorite(Request $request, EntityManagerInterface $em, $idClassName, $className) {
		if(empty($this->getUser()))
			return new JsonResponse(["error" => "You must be logged in"]);

		list($entity, $classNameVote) = $this->getNewEntity($em, $className, $idClassName);

		$entity = $em->getRepository($classNameVote)->findOneBy(["author" => $this->getUser(), "idClassVote" => $idClassName, "classNameVote" => $className, "valueVote" => null]);

		if(empty($entity)) {
			$entity = new $classNameVote();
			$entity->setFavorite(true);
		} else 
			$entity->setFavorite(!$entity->getFavorite());

		$entity->setClassNameVote($className);
		$entity->setIdClassVote($idClassName);
		$entity->setAuthor($this->getUser());
		
		$entity->setEntity($em->getRepository($entity->getMainEntityClassName())->find($idClassName));

		$em->persist($entity);
		$em->flush();

		$output = ["favorite" => $entity->getFavorite()];

		return new JsonResponse($output);
	}
}