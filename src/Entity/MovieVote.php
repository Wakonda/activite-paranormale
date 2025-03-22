<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Movies\Movie;

#[ORM\Table(name: 'movievote')]
#[ORM\Entity(repositoryClass: 'App\Repository\MovieVoteRepository')]
class MovieVote extends Vote
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Movies\Movie')]
    private $entity;

	public function getMainEntityClassName()
	{
		return Movie::class;
	}
	
	public function getClassName()
	{
		return 'MovieVote';
	}

    public function setEntity(Movie $entity) {
        $this->entity = $entity;
    }

    public function getEntity() {
        return $this->entity;
    }
}