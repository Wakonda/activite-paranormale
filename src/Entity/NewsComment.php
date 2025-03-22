<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'newscomment')]
#[ORM\Entity(repositoryClass: 'App\Repository\NewsCommentRepository')]
class NewsComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\News')]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return News::class;
	}
	
	public function getClassName()
	{
		return 'NewsComment';
	}

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}