<?php

namespace App\Entity\Stores;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class MovieStore extends Store {
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Movies\Movie")
     */
    protected $movie;

	public function getMovie()
    {
        return $this->movie;
    }

    public function setMovie($movie)
    {
        $this->movie = $movie;
    }
	
	public function __construct()
	{
		$this->setCategory(Store::MOVIE_CATEGORY);
	}
	
	public function getLinkedEntityName() {
		return $this->movie->getRealClass();
	}
}