<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Movies\Movie;

/**
 * @ORM\Table(name="movievote")
 * @ORM\Entity(repositoryClass="App\Repository\MovieVoteRepository")
 */
class MovieVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Movies\Movie")
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Movie::class;
	}
	
	public function getClassName()
	{
		return 'MovieVote';
	}

    /**
     * Set movie
     *
     * @param  App\Entity\Movies\Movie  $movie
     */
    public function setEntity(Movie $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Movies\Movie
     */
    public function getEntity()
    {
        return $this->entity;
    }
}