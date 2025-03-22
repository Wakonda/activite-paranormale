<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\CartographyCommentRepository')]
#[ORM\Table(name: 'cartographycomment')]
class CartographyComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Cartography')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Cartography::class;
	}
	
	public function getClassName()
	{
		return 'CartographyComment';
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