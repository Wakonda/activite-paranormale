<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Movies\Movie;

#[ORM\Table(name: 'moviecomment')]
#[ORM\Entity(repositoryClass: 'App\Repository\MovieCommentRepository')]
class MovieComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Movies\Movie')]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Movie::class;
	}
	
	public function getClassName()
	{
		return 'MovieComment';
	}

    public function setEntity(Movie $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}