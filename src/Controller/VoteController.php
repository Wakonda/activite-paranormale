<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

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
				$entity->setNews($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = NewsVote::class;
				break;
			case "Video":
				$entity = new VideoVote();
				$entity->setVideo($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = VideoVote::class;
				break;
			case "Photo":
				$entity = new PhotoVote();
				$entity->setPhoto($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = PhotoVote::class;
				break;
			case "Grimoire":
				$entity = new GrimoireVote();
				$entity->setGrimoire($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = GrimoireVote::class;
				break;
			case "Testimony":
				$entity = new TestimonyVote();
				$entity->setTestimony($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = TestimonyVote::class;
				break;
			case "Book":
				$entity = new BookVote();
				$entity->setBook($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = BookVote::class;
				break;
			case "WitchcraftTool":
				$entity = new WitchcraftToolVote();
				$entity->setWitchcraftTool($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = WitchcraftToolVote::class;
				break;
			case "EventMessage":
				$entity = new EventMessageVote();
				$entity->setEventMessage($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = EventMessageVote::class;
				break;
			case "Movie":
				$entity = new MovieVote();
				$entity->setEntity($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = MovieVote::class;
				break;
			case "TelevisionSerie":
				$entity = new TelevisionSerieVote();
				$entity->setEntity($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$className = TelevisionSerieVote::class;
				break;
		}
		return [$entity, $className];
	}

    public function indexAction(Request $request, $className, $idClassName)
    {
		$em = $this->getDoctrine()->getManager();
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
	
	public function editAction(Request $request, $className, $idClassName)
    {
		$em = $this->getDoctrine()->getManager();
		list($entity, $classNameVote) = $this->getNewEntity($em, $className, $idClassName);

		if(!$request->isXmlHttpRequest())
			throw $this->createNotFoundException('Unable to find this page.');

		$form = $this->createForm(VoteType::class, $entity);
		$form->handleRequest($request);

		if($request->isXmlHttpRequest())
		{
			$entity->setClassNameVote($classNameVote);
			$entity->setIdClassVote($idClassName);
		
			$em->persist($entity);
			$em->flush();
		}

		$averageVote = $em->getRepository($classNameVote)->averageVote($classNameVote, $idClassName);
		$countVoteByClassName = $em->getRepository($classNameVote)->countVoteByClassName($classNameVote, $idClassName);

		return new JsonResponse(['averageVote' => round($averageVote, 1), 'countVoteByClassName' => $countVoteByClassName]);
    }
}