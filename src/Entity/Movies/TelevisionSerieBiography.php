<?php

namespace App\Entity\Movies;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Service\APPurifierHTML;

use App\Entity\EntityLinkBiography;

/**
 * App\Entity\TelevisionSerieBiography
 *
 * @ORM\Table(name="televisionserie_biography")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\TelevisionSerieBiographyRepository")
 */
class TelevisionSerieBiography extends EntityLinkBiography implements MediaInterface
{
	/**
	 * @ORM\ManyToOne(targetEntity="TelevisionSerie", inversedBy="televisionSerieBiographies")
	 * @ORM\JoinColumn(name="televisionserie_id", referencedColumnName="id")
	 */
	private $televisionSerie;

	/**
	 * @ORM\ManyToOne(targetEntity="EpisodeTelevisionSerie", inversedBy="episodeTelevisionSerieBiographies")
	 * @ORM\JoinColumn(name="episodetelevisionserie_id", referencedColumnName="id")
	 */
	private $episodeTelevisionSerie;

	public static function getOccupations(): Array {
		return [
			self::ACTOR_OCCUPATION,
			self::PRODUCER_OCCUPATION,
			self::SCREENWRITER_OCCUPATION,
			self::DIRECTOR_OCCUPATION
		];
	}

    /**
     * Set televisionSerie
     *
     * @param string $televisionSerie
     */
    public function setTelevisionSerie($televisionSerie)
    {
        $this->televisionSerie = $televisionSerie;
    }

    /**
     * Get televisionSerie
     *
     * @return string 
     */
    public function getTelevisionSerie()
    {
        return $this->televisionSerie;
    }

    /**
     * Set biography
     *
     * @param string $biography
     */
    public function setEpisodeTelevisionSerie($episodeTelevisionSerie)
    {
        $this->episodeTelevisionSerie = $episodeTelevisionSerie;
    }

    /**
     * Get episodeTelevisionSerie
     *
     * @return string 
     */
    public function getEpisodeTelevisionSerie()
    {
        return $this->episodeTelevisionSerie;
    }
}