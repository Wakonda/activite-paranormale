<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\EventMessage;

#[ORM\Entity]
#[ORM\Table(name: 'eventmessagetags')]
class EventMessageTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\EventMessage')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return EventMessage::class;
	}
	
	public function getClassName()
	{
		return 'EventMessageTags';
	}

    public function setEntity(EventMessage $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}