<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'creepystorytags')]
class CreepyStoryTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\CreepyStory')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return CreepyStory::class;
	}
	
	public function getClassName()
	{
		return 'CreepyStoryTags';
	}

    public function setEntity(CreepyStory $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}