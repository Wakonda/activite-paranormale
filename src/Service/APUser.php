<?php
	namespace App\Service;

	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Entity\User;
	use App\Entity\News;
	use App\Entity\Testimony;
	use App\Entity\EventMessage;
	use App\Entity\Grimoire;

	class APUser
	{
		private $em;
		
		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
		}

		public function countContributionByUser($user, $displayState = 1)
		{
			$repository = $this->em->getRepository(User::class);
		
			$contributionsArray = array(
				"news" => $repository->getUsersContribution($user, News::class, 0, 0, 0, 0, 0, true, $displayState),
				"testimony" => $repository->getUsersContribution($user, Testimony::class, 0, 0, 0, 0, 0, true, $displayState),
				"event" => $repository->getUsersContribution($user, EventMessage::class, 0, 0, 0, 0, 0, true, $displayState),
				"witchcraft" => $repository->getUsersContribution($user, Grimoire::class, 0, 0, 0, 0, 0, true, $displayState)
			);
			
			if($displayState == 1)
				$contributionsArray["comment"] = $repository->getUsersCommentContribution($user, null, "", 0, 0, 0, 0, true);

			$contributionsArray['total'] = array_sum($contributionsArray);
			
			return $contributionsArray;
		}
	}