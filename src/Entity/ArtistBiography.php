<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;

#[ORM\Table(name: 'artist_biography')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\ArtistBiographyRepository')]
class ArtistBiography extends EntityLinkBiography
{
	const VOCAL_OCCUPATION = "vocal";
	const GUITAR_OCCUPATION = "guitar";
	const BASS_OCCUPATION = "bass";
	const DRUM_OCCUPATION = "drum";
	const KEYBOARD_OCCUPATION = "keyboard";
	const VIOLIN_OCCUPATION = "violin";

	#[ORM\ManyToOne(targetEntity: 'Artist', inversedBy: 'artistBiographies')]
	#[ORM\JoinColumn(name: 'artist_id', referencedColumnName: 'id')]
	private $artist;

	#[ORM\Column(name: 'startYear', type: 'string', length: 10, nullable: true)]
	private $startYear;

	#[ORM\Column(name: 'endYear', type: 'string', length: 10, nullable: true)]
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

    public function setArtist($artist)
    {
        $this->artist = $artist;
    }

    public function getArtist()
    {
        return $this->artist;
    }

    public function setStartYear($startYear)
    {
        $this->startYear = $startYear;
    }

    public function getStartYear()
    {
        return $this->startYear;
    }

    public function setEndYear($endYear)
    {
        $this->endYear = $endYear;
    }

    public function getEndYear()
    {
        return $this->endYear;
    }
}