<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
		}

		$entity->setEntity($em->getRepository($entity->getMainEntityClassName())->find($idClassName));

		return [$entity, $className];
	}

    public function indexAction(Request $request, EntityManagerInterface $em, $className, $idClassName)
    {
		list($entity, $classNameVote) = $this->getNewEntity($em, $className, $idClassName);

		$averageVote = $em->getRepository($classNameVote)->averageVote($className, $idClassName);

		if($averageVote == null)
			$averageVote = null;

		$form = $this->createForm(VoteType::class, $entity, ["averageVote" => $averageVote]);
		$countVoteByClassName = $em->getRepository($classNameVote)->countVoteByClassName($classNameVote, $idClassName);

        return $this->render('vote/Vote/index.html.twig', [
			'className' => $className,
			'idClassName' => $idClassName,
			'countVoteByClassName' => $countVoteByClassName,
			'averageVote' => $averageVote,
			'form' => $form->createView(),
		]);
    }
	
	public function editAction(Request $request, EntityManagerInterface $em, $className, $idClassName)
    {
		list($entity, $classNameVote) = $this->getNewEntity($em, $className, $idClassName);

		if(!$request->isXmlHttpRequest())
			throw $this->createNotFoundException('Unable to find this page.');

		$form = $this->createForm(VoteType::class, $entity);
		$form->handleRequest($request);

		if($request->isXmlHttpRequest())
		{
			$entity->setClassNameVote($classNameVote);
			$entity->setIdClassVote($idClassName);
			$entity->setAuthor($this->getUser());

			$em->persist($entity);
			$em->flush();
		}

		$averageVote = $em->getRepository($classNameVote)->averageVote($classNameVote, $idClassName);
		$countVoteByClassName = $em->getRepository($classNameVote)->countVoteByClassName($classNameVote, $idClassName);

		return new JsonResponse(['averageVote' => round($averageVote, 1), 'countVoteByClassName' => $countVoteByClassName]);
    }

	public function listVoteByUser($authorId) {
		return $this->render("vote/Vote/index_user.html.twig");
	}

	public function listVoteByUserDatatables(Request $request, EntityManagerInterface $em, TranslatorInterface $translator, $authorId) {
		$iDisplayStart = $request->query->get('start');
		$iDisplayLength = $request->query->get('length');
		// dd($request->query->all());
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
				1 => "danger",
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
}