<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'bookcomment')]
#[ORM\Entity(repositoryClass: 'App\Repository\BookCommentRepository')]
class BookComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Book')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Book::class;
	}
	
	public function getClassName()
	{
		return 'BookComment';
	}

    public function setEntity(Book $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}