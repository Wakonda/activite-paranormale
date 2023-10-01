<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;
	use Twig\TwigFunction;

	use Doctrine\ORM\EntityManagerInterface;

	use App\Entity\Comment;
	use App\Entity\NewsComment;

	class APCommentExtension extends AbstractExtension
	{
		private $em;
		
		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
		}
		
		public function getFilters()
		{
			return array(
				new TwigFilter('count_comments_by_article', [$this, 'countCommentsByArticleFilter']),
				new TwigFilter('count_comments_by_state', [$this, 'countCommentsByStateFilter'])
			);
		}
		
		public function getFunctions()
		{
			return array(
				new TwigFunction('approved_state', [$this, 'getApprovedState']),
				new TwigFunction('notChecked_state', [$this, 'getNotCheckedState']),
				new TwigFunction('denied_state', [$this, 'getDeniedState']),
				new TwigFunction('child_comments', [$this, 'getChildComments'])
			);
		}

		// Filters
		public function countCommentsByArticleFilter($entity)
		{
			$count = $this->em->getRepository(NewsComment::class)->countComment($entity->getId());
			return $count;
		}
		
		public function countCommentsByStateFilter($state)
		{
			return $this->em->getRepository(Comment::class)->countAllCommentsByState($state);
		}

		public function getApprovedState()
		{
			return Comment::$approved;
		}

		public function getNotCheckedState()
		{
			return Comment::$notChecked;
		}

		public function getDeniedState()
		{
			return Comment::$denied;
		}

		public function getChildComments($parentComment) {
			return $this->em->getRepository(Comment::class)->findBy(["parentComment" => $parentComment]);
		}

		public function getName()
		{
			return 'ap_commentextension';
		}
	}