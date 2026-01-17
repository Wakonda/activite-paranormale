<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Comment;
use App\Entity\NewsComment;
use App\Entity\TestimonyComment;
use App\Entity\VideoComment;
use App\Entity\GrimoireComment;
use App\Entity\PhotoComment;
use App\Entity\BookComment;
use App\Entity\WitchcraftToolComment;
use App\Entity\EventMessageComment;
use App\Entity\MovieComment;
use App\Entity\CartographyComment;
use App\Entity\DocumentComment;
use App\Entity\TelevisionSerieComment;
use App\Form\Type\CommentType;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/comment')]
class CommentController extends AbstractController
{
	public static $nbrMessageByPage = 7;
	
    private function getNewEntity($em, $className, $idClassName)
	{
		switch($className)
		{
			case "News":
				$entity = new NewsComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("News_ReadNews_New", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = NewsComment::class;
				break;
			case "Video":
				$entity = new VideoComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("Video_Read", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()] , UrlGeneratorInterface::ABSOLUTE_URL);
				$className = VideoComment::class;
				break;
			case "Photo":
				$entity = new PhotoComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("Photo_Read", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = PhotoComment::class;
				break;
			case "Grimoire":
				$entity = new GrimoireComment();
				$entity->setEntity($em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("Witchcraft_ReadGrimoire_Simple", ["id" => $idClassName], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = GrimoireComment::class;
				break;
			case "Testimony":
				$entity = new TestimonyComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("Testimony_Show", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = TestimonyComment::class;
				break;
			case "Book":
				$entity = new BookComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("Book_Show", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = BookComment::class;
				break;
			case "WitchcraftTool":
				$entity = new WitchcraftToolComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("WitchcraftTool_Show", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = WitchcraftToolComment::class;
				break;
			case "EventMessage":
				$entity = new EventMessageComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("EventMessage_Read", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = EventMessageComment::class;
				break;
			case "Movie":
				$entity = new MovieComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("Movie_Show", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = MovieComment::class;
				break;
			case "TelevisionSerie":
				$entity = new TelevisionSerieComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("TelevisionSerie_Show", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = TelevisionSerieComment::class;
				break;
			case "Cartography":
				$entity = new CartographyComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("Cartography_Show", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = CartographyComment::class;
				break;
			case "Document":
				$entity = new DocumentComment();
				$entity->setEntity($me = $em->getRepository($entity->getMainEntityClassName())->find($idClassName));
				$path = $this->generateUrl("DocumentBundle_AbstractDocument", ["id" => $idClassName, "title_slug" => $me->getUrlSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
				$className = DocumentComment::class;
				break;
		}

		return [$entity, $path, $className];
	}

	#[Route('/{idClassName}/{className}', name: 'APCommentBundle_homepage')]
    public function index(Request $request, EntityManagerInterface $em, $idClassName, $className)
    {
		list($entity, $path, $classNameComment) = $this->getNewEntity($em, $className, $idClassName);

		$user = $this->getUser();

		$countComment = $em->getRepository($classNameComment)->countComment($idClassName);
		$nbrOfPages = ceil($countComment/self::$nbrMessageByPage);

		if($request->attributes->get('page') != NULL)
			$page = $request->attributes->get('page');
		else
			$page = 1;

		if(!is_object($user))
			$anonymousComment = true;
		else
			$anonymousComment = false;

		$form = $this->createForm(CommentType::class, $entity, ['userType' => $anonymousComment]);
		$entities = $em->getRepository($classNameComment)->getShowComment(self::$nbrMessageByPage, $page, $idClassName);
		
        return $this->render('comment/Comment/index.html.twig', [
			'idClassName' => $idClassName,
			'entities' => $entities,
			'className' => $className,
			'commentType' => $form->createView(),
			'nbrOfPages' => $nbrOfPages,
			'nbrMessageByPage' => self::$nbrMessageByPage,
			'currentPage' => $page,
			'path' => $path
		]);
    }

	#[Route('/create/{idClassName}/{className}', name: 'APCommentBundle_create', methods: ['POST', 'GET'])]
    public function create(Request $request, EntityManagerInterface $em, $idClassName, $className)
    {
		list($entity, $path, $classNameComment) = $this->getNewEntity($em, $className, $idClassName);

		$user = $this->getUser();
        
		if(!is_object($user))
			$anonymousComment = true;
		else
			$anonymousComment = false;

		$commentType = $this->createForm(CommentType::class, $entity, ['userType' => $anonymousComment]);
        
        $commentType->handleRequest($request);
		
		$entities = [];

		if($request->isXmlHttpRequest())
		{
            if($commentType->isValid())
            {
				if(!is_object($user))
				{
					$entity->setAnonymousComment(1);
				}
				else
				{
					$entity->setAnonymousComment(0);
					$entity->setAuthorComment($user);
					$entity->setEmailComment($user->getEmail());
					$entity->setAnonymousAuthorComment(0);
				}

                $em->persist($entity);
                $em->flush();

                list($entity, $path) = $this->getNewEntity($em, $className, $idClassName);
                $commentType  = $this->createForm(CommentType::class, $entity, ['userType' => $anonymousComment]);
			}

			$page = 1;
			$countComment = $em->getRepository($classNameComment)->countComment($idClassName);
			$nbrOfPages = ceil($countComment/self::$nbrMessageByPage);
			$entities = $em->getRepository($classNameComment)->getShowComment(self::$nbrMessageByPage, $page, $idClassName);
		}

		return $this->render('comment/Comment/edit.html.twig', [
			'idClassName' => $idClassName,
			'className' => $className,
			'entities' => $entities,
			'commentType' => $commentType->createView(),
			'entity' => $entity,
			'nbrOfPages' => $nbrOfPages,
			'nbrMessageByPage' => self::$nbrMessageByPage,
			'currentPage' => $page,
			'path' => $path
		]);
    }

	#[Route('/reply/{idClassName}/{className}', name: 'APCommentBundle_reply', methods: ['POST', 'GET'])]
	public function reply(Request $request, EntityManagerInterface $em, FormFactoryInterface $formFactory, $idClassName, $className) {
		list($entity, $path, $classNameComment) = $this->getNewEntity($em, $className, $idClassName);

		$parentComment = null;
		
		if($request->query->has("commentId"))
			$parentComment = $em->getRepository(Comment::class)->find($request->query->get("commentId"));

		$user = $this->getUser();
        
		if(!is_object($user))
			$anonymousComment = true;
		else
			$anonymousComment = false;
		
		$commentType = $formFactory->createNamed("reply_comment", CommentType::class, $entity, ['userType' => $anonymousComment]);

		if($request->isXmlHttpRequest() and $request->isMethod('POST'))
		{
			$commentType->handleRequest($request);
            if($commentType->isValid())
            {
				$entity->setParentComment($parentComment);

				if(!is_object($user))
				{
					$entity->setAnonymousComment(1);
				}
				else
				{
					$entity->setAnonymousComment(0);
					$entity->setAuthorComment($user);
					$entity->setEmailComment($user->getEmail());
					$entity->setAnonymousAuthorComment(0);
				}

                $em->persist($entity);
                $em->flush();

                list($entity, $path) = $this->getNewEntity($em, $className, $idClassName);
                $commentType  = $this->createForm(CommentType::class, $entity, ['userType' => $anonymousComment]);

				$page = 1;
				$countComment = $em->getRepository($classNameComment)->countComment($idClassName);
				$nbrOfPages = ceil($countComment/self::$nbrMessageByPage);
				$entities = $em->getRepository($classNameComment)->getShowComment(self::$nbrMessageByPage, $page, $idClassName);

				$content = $this->render('comment/Comment/edit.html.twig', [
					'idClassName' => $idClassName,
					'className' => $className,
					'entities' => $entities,
					'commentType' => $commentType->createView(),
					'entity' => $entity,
					'nbrOfPages' => $nbrOfPages,
					'nbrMessageByPage' => self::$nbrMessageByPage,
					'currentPage' => $page,
					'path' => $path
				])->getContent();

				return new JsonResponse(["status" => "success", "content" => $content]);
			} else {
				$content = $this->render('comment/Comment/reply.html.twig', [
					'idClassName' => $idClassName,
					'className' => $className,
					'commentReplyType' => $commentType->createView(),
					'entity' => $entity,
					'path' => $path,
					'parentComment' => $parentComment
				])->getContent();

				return new JsonResponse(["status" => "error", "content" => $content]);	
			}
		}

		return $this->render('comment/Comment/reply.html.twig', [
			'idClassName' => $idClassName,
			'className' => $className,
			'commentReplyType' => $commentType->createView(),
			'entity' => $entity,
			'path' => $path,
			'parentComment' => $parentComment
		]);
	}

	#[Route('/pagination/{idClassName}/{className}', name: 'APCommentBundle_pagination')]
	public function pagination(Request $request, EntityManagerInterface $em, $idClassName, $className) {
		list($entity, $path, $classNameComment) = $this->getNewEntity($em, $className, $idClassName);
		$countComment = $em->getRepository($classNameComment)->countComment($idClassName);
		$nbrOfPages = ceil($countComment/self::$nbrMessageByPage);

		if($request->request->get("page") != NULL)
			$page = $request->request->get('page');
		else
			$page = 1;
		
		$nbrOfPages = ceil($countComment/self::$nbrMessageByPage);
		
		$entities = $em->getRepository($classNameComment)->getShowComment(self::$nbrMessageByPage, $page, $idClassName);

		return $this->render("comment/Comment/list.html.twig", [
		"entities" => $entities,
		'currentPage' => $page,
		"nbrMessageByPage" => self::$nbrMessageByPage,
		"nbrOfPages" => $nbrOfPages,
		'idClassName' => $idClassName,
		'className' => $className]);
	}
}