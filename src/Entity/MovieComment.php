<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Movies\Movie;

/**
 * @ORM\Table(name="moviecomment")
 * @ORM\Entity(repositoryClass="App\Repository\MovieCommentRepository")
 */
class MovieComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Movies\Movie")
	*/
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

    /**
     * Set entity
     *
     * @param  App\Entity\Movies\Movie  $entity
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