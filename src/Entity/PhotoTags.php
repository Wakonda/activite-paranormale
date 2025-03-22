<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'phototags')]
class PhotoTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Photo')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return Photo::class;
	}
	
	public function getClassName()
	{
		return 'PhotoTags';
	}

    public function setEntity(Photo $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}