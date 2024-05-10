<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;

/**
 * App\Entity\ArtistBiography
 *
 * @ORM\Table(name="artist_biography")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\ArtistBiographyRepository")
 */
class ArtistBiography extends EntityLinkBiography
{
	const VOCAL_OCCUPATION = "vocal";
	const GUITAR_OCCUPATION = "guitar";
	const BASS_OCCUPATION = "bass";
	const DRUM_OCCUPATION = "drum";
	const KEYBOARD_OCCUPATION = "keyboard";
	const VIOLIN_OCCUPATION = "violin";

	/**
	 * @ORM\ManyToOne(targetEntity="Artist", inversedBy="artistBiographies")
	 * @ORM\JoinColumn(name="artist_id", referencedColumnName="id")
	 */
	private $artist;

	/**
	 * @ORM\Column(name="startYear", type="string", length=10, nullable=true)
	 */
	private $startYear;

	/**
	 * @ORM\Column(name="endYear", type="string", length=10, nullable=true)
	 */
	private $endYear;
	
	public static function getOccupations(): Array {
		return [
			self::VOCAL_OCCUPATION,
			self::GUITAR_OCCUPATION,
			self::BASS_OCCUPATION,
			self::DRUM_OCCUPATION,
			self::KEYBOARD_OCCUPATION,
			self::VIOLIN_OCCUPATION
		];
	}

    /**
     * Set artist
     *
     * @param string $artist
     */
    public function setArtist($artist)
    {
        $this->artist = $artist;
    }

    /**
     * Get artist
     *
     * @return string 
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * Set startYear
     *
     * @param integer $startYear
     */
    public function setStartYear($startYear)
    {
        $this->startYear = $startYear;
    }

    /**
     * Get startYear
     *
     * @return integer
     */
    public function getStartYear()
    {
        return $this->startYear;
    }

    /**
     * Set endYear
     *
     * @param integer $endYear
     */
    public function setEndYear($endYear)
    {
        $this->endYear = $endYear;
    }

    /**
     * Get endYear
     *
     * @return integer
     */
    public function getEndYear()
    {
        return $this->endYear;
    }
}