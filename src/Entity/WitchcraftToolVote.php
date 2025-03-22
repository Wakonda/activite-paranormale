<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\WitchcraftToolVoteRepository')]
#[ORM\Table(name: 'witchcrafttoolvote')]
class WitchcraftToolVote extends Vote
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\WitchcraftTool')]
	#[ORM\JoinColumn(name: 'witchcraftTool_id', nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return WitchcraftTool::class;
	}
	
	public function getClassName()
	{
		return 'WitchcraftToolVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity($entity) {
        $this->entity = $entity;
    }
}