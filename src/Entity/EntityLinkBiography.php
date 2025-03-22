<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;

#[ORM\Table(name: 'entity_link_biography')]
#[ORM\Entity(repositoryClass: 'App\Repository\EntityLinkBiographyRepository')]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap([
    "entity_link_biography" => "EntityLinkBiography",
    "artist_biography" => "ArtistBiography",
    "book_edition_biography" => "BookEditionBiography",
    "movie_biography" => "App\Entity\Movies\MovieBiography",
    "televisionserie_biography" => "App\Entity\Movies\TelevisionSerieBiography",
    "music_biography" => "App\Entity\MusicBiography",
])]
class EntityLinkBiography implements Interfaces\BiographyInterface
{
	const UFOLOGIST_OCCUPATION = "ufologist";
	const THEOLOGIAN_OCCUPATION = "theologian";
	const RELIGIOUS_OCCUPATION = "religious";
	const OCCULTIST_OCCUPATION = "occultist";
	const ALCHEMIST_OCCUPATION = "alchemist";
	const POLITICIAN_OCCUPATION = "politician";
	const SCIENTIST_OCCUPATION = "scientist";
	const SAINT_OCCUPATION = "saint";

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'role', type: 'string', length: 255, nullable: true)]
	private $role;

	#[ORM\Column(name: 'occupation', type: 'string', length: 255)]
	private $occupation;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Biography', cascade: ['persist'])]
	#[ORM\JoinColumn(name: 'biography_id', referencedColumnName: 'id')]
	private $biography;

	public static function getOccupations(): Array {
		return [
			self::UFOLOGIST_OCCUPATION,
			self::THEOLOGIAN_OCCUPATION,
			self::RELIGIOUS_OCCUPATION,
			self::OCCULTIST_OCCUPATION,
			self::ALCHEMIST_OCCUPATION,
			self::POLITICIAN_OCCUPATION,
			self::SCIENTIST_OCCUPATION,
			self::SAINT_OCCUPATION
		];
	}

    public function getId() {
        return $this->id;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setOccupation($occupation)
    {
        $this->occupation = $occupation;
    }

    public function getOccupation()
    {
        return $this->occupation;
    }

    public function setBiography($biography)
    {
        $this->biography = $biography;
    }

    public function getBiography()
    {
        return $this->biography;
    }
}