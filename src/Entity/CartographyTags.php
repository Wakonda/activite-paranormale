<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cartographytags')]
class CartographyTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Cartography')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return Cartography::class;
	}
	
	public function getClassName()
	{
		return 'CartographyTags';
	}

    public function setEntity(Cartography $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}