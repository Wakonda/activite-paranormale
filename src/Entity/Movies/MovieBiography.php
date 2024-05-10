<?php

namespace App\Entity\Movies;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;
use App\Entity\EntityLinkBiography;

/**
 * App\Entity\MovieBiography
 *
 * @ORM\Table(name="movie_biography")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\MovieBiographyRepository")
 */
class MovieBiography extends EntityLinkBiography implements MediaInterface
{
	/**
	 * @ORM\ManyToOne(targetEntity="Movie", inversedBy="movieBiographies")
	 * @ORM\JoinColumn(name="movie_id", referencedColumnName="id")
	 */
	private $movie;

	public static function getOccupations(): Array {
		return [
			self::ACTOR_OCCUPATION,
			self::PRODUCER_OCCUPATION,
			self::SCREENWRITER_OCCUPATION,
			self::DIRECTOR_OCCUPATION,
			self::DIRECTOROFPHOTOGRAPHY_OCCUPATION,
			self::FILMEDITOR_OCCUPATION,
			self::COMPOSER_OCCUPATION,
			self::EXECUTIVEPRODUCER_OCCUPATION,
			self::COSTUMEDESIGNER_OCCUPATION
		];
	}

    /**
     * Set movie
     *
     * @param string $movie
     */
    public function setMovie($movie)
    {
        $this->movie = $movie;
    }

    /**
     * Get movie
     *
     * @return string 
     */
    public function getMovie()
    {
        return $this->movie;
    }
}