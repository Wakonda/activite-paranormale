<?php

namespace App\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class InternationalNameFieldListener implements EventSubscriberInterface
{
	private $uniqId = true;
	
	public function __construct($uniqId = true) {
		$this->uniqId = $uniqId;
	}
	
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT   => 'onPreSubmit',
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
		$data = $event->getData();

		if(empty($data["internationalName"]) and !empty($title = $data["title"])) {
			$generator = new \Ausi\SlugGenerator\SlugGenerator;
			$data["internationalName"] = $generator->generate($title).($this->uniqId ? uniqid() : "");
			$event->setData($data);
		}
    }
}