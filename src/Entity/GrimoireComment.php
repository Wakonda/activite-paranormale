<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\GrimoireCommentRepository')]
#[ORM\Table(name: 'grimoirecomment')]
class GrimoireComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Grimoire')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Grimoire::class;
	}
	
	public function getClassName()
	{
		return 'GrimoireComment';
	}

    public function setEntity(Grimoire $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}