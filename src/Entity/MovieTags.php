<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Movies\Movie;

#[ORM\Entity]
#[ORM\Table(name: 'movietags')]
class MovieTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Movies\Movie')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return Movie::class;
	}
	
	public function getClassName()
	{
		return 'MovieTags';
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