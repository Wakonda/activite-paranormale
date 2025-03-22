<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'booktags')]
class BookTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Book')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return Book::class;
	}
	
	public function getClassName()
	{
		return 'BookTags';
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