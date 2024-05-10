<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Movies\Movie;

/**
 * @ORM\Entity()
 * @ORM\Table(name="movietags")
 */
class MovieTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Movies\Movie")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Movie::class;
	}
	
	public function getClassName()
	{
		return 'MovieTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Movies\Movie $entity
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