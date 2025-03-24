<?php

namespace App\Entity\Movies;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;
use App\Entity\EntityLinkBiography;

#[ORM\Table(name: 'televisionserie_biography')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\TelevisionSerieBiographyRepository')]
class TelevisionSerieBiography extends EntityLinkBiography implements MediaInterface
{
	#[ORM\ManyToOne(targetEntity: 'TelevisionSerie', inversedBy: 'televisionSerieBiographies')]
	#[ORM\JoinColumn(referencedColumnName: 'id')]
	private $televisionSerie;

	#[ORM\ManyToOne(targetEntity: 'EpisodeTelevisionSerie', inversedBy: 'episodeTelevisionSerieBiographies')]
	#[ORM\JoinColumn(name: 'episodetelevisionserie_id', referencedColumnName: 'id')]
	private $episodeTelevisionSerie;

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

    public function setTelevisionSerie($televisionSerie)
    {
        $this->televisionSerie = $televisionSerie;
    }

    public function getTelevisionSerie()
    {
        return $this->televisionSerie;
    }

    public function setEpisodeTelevisionSerie($episodeTelevisionSerie)
    {
        $this->episodeTelevisionSerie = $episodeTelevisionSerie;
    }

    public function getEpisodeTelevisionSerie()
    {
        return $this->episodeTelevisionSerie;
    }
}