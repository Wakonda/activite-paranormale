<?php

namespace App\Listener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\UsageTrackingTokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Entity\History;
use App\Entity\HistoryDetail;

class HistoryListener
{
    private $requestStack;
	private $tokenStorage;
	private $authorizationChecker;

    public function __construct(RequestStack $requestStack, UsageTrackingTokenStorage $tokenStorage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
		$this->authorizationChecker = $authorizationChecker;
    }

    public function postUpdate (PostUpdateEventArgs $args)
    {
		$uow = $args->getObjectManager()->getUnitOfWork();
		$entity = $args->getObject();
		
		if(!method_exists($entity, "getHistory"))
			return;
		
		if(empty($this->requestStack->getCurrentRequest()))
			return;
		
		$username = null;

		if(!empty($this->tokenStorage->getToken()) and $this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY'))
			$username = $this->tokenStorage->getToken()->getUser()->getUsername();
		else
			$username = $entity->authorToString();
		
		if(empty($username))
			return;

		$changeSetArray = $uow->getEntityChangeSet($entity);
		
		if(!empty($changeSetArray) and isset($changeSetArray["history"]))
			unset($changeSetArray["history"]);
		
		if(empty($changeSetArray))
			return;

		if(empty($entity->getHistory())) {
			$history = new History();
			$entity->setHistory($history);

			$args->getEntityManager()->persist($entity);
		}

		$builder = new UnifiedDiffOutputBuilder(
			"", // custom header
			true                      // do not add line numbers to the diff 
		);
		
		$differ = new Differ($builder);
		$text = [];

		foreach($changeSetArray as $changeSet) {
			if(isset($changeSet[0]) and isset($changeSet[1])) {
				$originalText = $changeSet[0];

				if(is_object($originalText) and !method_exists($originalText, "__toString"))
					continue;
				elseif(is_object($originalText))
					$originalText = $originalText->__toString();
				
				if(is_object($changeSet[1]) and !method_exists($changeSet[1], "__toString"))
					continue;
				elseif(is_object($changeSet[1]))
					$newText = $changeSet[1]->__toString();
				else
					$newText = $changeSet[1];
				
				if(!is_string($newText))
					continue;
				
				$newText = str_replace(["\r"], '', str_replace(["\n"], ' ', $newText));

				if($changeSet[0] == $newText)
					continue;

				if(is_array($originalText))
					$originalText = json_encode($originalText);

				if(is_array($newText))
					$newText = json_encode($newText);

				$text[] = $differ->diff(htmlentities($originalText), htmlentities($newText));
			}
		}

		$historyDetail = new HistoryDetail();
		$history = $entity->getHistory();

		$historyDetail->setIpAddress($this->getClientIp($this->requestStack->getCurrentRequest()));
		$historyDetail->setUser($username);
		$historyDetail->setDiffText('<div class="diff">'.implode("<br>", $text).'</div>');
		
		$history->addHistoryDetail($historyDetail);
		
		$args->getObjectManager()->persist($history);
		$args->getObjectManager()->persist($historyDetail);
		 
		$args->getObjectManager()->flush();
    }
	
    public function postPersist(PostPersistEventArgs $args)
    {
    }
	
	private function formatHistory($text) {
		return implode(PHP_EOL, array_map(function ($string) {
			$string = preg_replace('/(@@ [A-Za-z0-9,\-+\s]* @@)/', '<div class="alert alert-info fw-bold mb-0">$1</div>', $string);
			// $string = preg_replace('/((\\+){3} New)/', '<div class="alert alert-success fw-bold mb-0">$1</div>', $string);
			$string = preg_replace('/^(\\+){1}/', '<div class="alert alert-success fw-bold my-2"><i class="fas fa-plus"></i></div>', $string);
			// $string = preg_replace('/^((\\-){3} Original)/', '<div class="alert alert-danger fw-bold mb-0">$1</div>', $string);
			$string = preg_replace('/^(\\-){1}/', '<div class="alert alert-danger fw-bold my-2"><i class="fas fa-minus"></i></div>', $string);
			$string = str_repeat(' ', 6) . $string;
			return $string;
		}, explode(PHP_EOL, $text)));
	}
	
	private function getClientIp(Request $request)
	{
		$ip = "UNKNOWN IP";
		$server = $request->server;
		
		if ($server->has('HTTP_CLIENT_IP')) {
			$ip = $server->get('HTTP_CLIENT_IP');
		} elseif ($server->has('HTTP_X_FORWARDED_FOR')) {
			$ip = $server->get('HTTP_X_FORWARDED_FOR');
		} else {
			$ip = $server->get('REMOTE_ADDR');
		}
		
		return $ip;
	}
}