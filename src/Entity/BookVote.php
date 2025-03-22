<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\BookVoteRepository')]
#[ORM\Table(name: 'bookvote')]
class BookVote extends Vote
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Book')]
	#[ORM\JoinColumn(name: 'book_id', nullable: false)]
    private $entity;

	public function getMainEntityClassName() {
		return Book::class;
	}

	public function getClassName()
	{
		return 'BookVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity($entity) {
        $this->entity = $entity;
    }
}