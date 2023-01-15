<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\MusicBiography
 *
 * @ORM\Table(name="music_biography")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\MusicBiographyRepository")
 */
class MusicBiography extends EntityLinkBiography
{
	const VOCAL_OCCUPATION = "vocal";
	const GUITAR_OCCUPATION = "guitar";
	const BASS_OCCUPATION = "bass";
	const DRUM_OCCUPATION = "drum";
	const KEYBOARD_OCCUPATION = "keyboard";
	const VIOLIN_OCCUPATION = "violin";

	/**
	 * @ORM\ManyToOne(targetEntity="Music", inversedBy="musicBiographies")
	 * @ORM\JoinColumn(name="music_id", referencedColumnName="id")
	 */
	private $music;
	
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
     * Set music
     *
     * @param string $music
     */
    public function setMusic($music)
    {
        $this->music = $music;
    }

    /**
     * Get music
     *
     * @return string 
     */
    public function getMusic()
    {
        return $this->music;
    }
}