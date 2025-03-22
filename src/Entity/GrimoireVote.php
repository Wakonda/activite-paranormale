<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\GrimoireVoteRepository')]
#[ORM\Table(name: 'grimoirevote')]
class GrimoireVote extends Vote
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Grimoire')]
	#[ORM\JoinColumn(name: 'grimoire_id', nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return Grimoire::class;
	}
	
	public function getClassName()
	{
		return 'GrimoireVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity($entity) {
        $this->entity = $entity;
    }
}