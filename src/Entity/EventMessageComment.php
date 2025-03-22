<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\EventMessageCommentRepository')]
#[ORM\Table(name: 'eventmessagecomment')]
class EventMessageComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\EventMessage')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return EventMessage::class;
	}
	
	public function getClassName()
	{
		return 'EventMessageComment';
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