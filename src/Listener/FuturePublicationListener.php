<?php

namespace App\Listener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Service\TwitterAPI;

class FuturePublicationListener
{
	private $em;
	private $router;

	public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $router)
	{
		$this->em = $em;
		$this->router = $router;
	}

	public function onKernelRequest(RequestEvent $event)
	{
		/*$state = $this->em->getRepository('APIndexBundle:State')->findOneBy(array("internationalName" => "Draft"));
		$entities = $this->em->getRepository('APNewsBundle:News')->findBy(array("state" => $state));

		foreach($entities as $entity)
		{
			if($entity->getPublicationDate() <= new \Datetime())
			{
				$newState = $this->em->getRepository('APIndexBundle:State')->findOneBy(array("internationalName" => "Validate"));
				$entity->setState($newState);
				$this->em->persist($entity);
				$this->em->flush();
				
				// Publish on Twitter
				$currentURL = $this->router->generate("News_ReadNews_New", array("id" => 2, "title" => "kkk"), true);

				$search = array("ovni", "ufo", "ghost", "fantôme");
				$subject = array("#ovni", "#ufo", "#ghost", "#fantôme");
				$res = str_ireplace($search, $subject, $entity->getTitle());

				$twitterAPI = new TwitterAPI();

				$twitterAPI->setLanguage($entity->getLanguage()->getAbbreviation());
				$twitterAPI->sendTweet($res." ".$currentURL);
			}
		}*/
	}
}