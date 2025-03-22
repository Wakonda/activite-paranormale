<?php

namespace App\Entity\Movies;

use App\Entity\MappedSuperclassBase;
use App\Entity\Movies\GenreAudiovisual;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Region;
use App\Entity\FileManagement;

#[ORM\Table(name: 'televisionserie')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\TelevisionSerieRepository')]
class TelevisionSerie extends MappedSuperclassBase
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'text', nullable: true)]
	protected $introduction;

	#[ORM\OneToOne(targetEntity: 'App\Entity\FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Movies\GenreAudiovisual')]
    protected $genre;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Region')]
    protected $country;

	#[ORM\OneToMany(targetEntity: TelevisionSerieBiography::class, mappedBy: 'televisionSerie', cascade: ['persist'])]
	#[ORM\JoinTable(name: 'televisionserie_biography', joinColumns: [new ORM\JoinColumn(name: 'televisionSerie_id',	referencedColumnName: 'id',	onDelete: 'cascade')],
		inverseJoinColumns: [new ORM\JoinColumn(name: 'biography_id', referencedColumnName: 'id', onDelete: 'cascade')]
	)]
	private $televisionSerieBiographies;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'identifiers', type: 'text', nullable: true)]
    private $identifiers;

	public function __construct()
	{
		parent::__construct();
	}

	public function __toString(): string
	{
		return $this->title;
	}

	public function getShowRoute()
	{
		return "TelevisionSerie_Show";
	}

    public function getId()
    {
        return $this->id;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/televisionserie/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../../public/'.$this->getAssetImagePath();
    }
	
	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function getGenre(): ?GenreAudiovisual
    {
        return $this->genre;
    }

    public function setGenre(GenreAudiovisual $genre)
    {
        $this->genre = $genre;
    }

    public function getCountry(): ?Region
    {
        return $this->country;
    }

    public function setCountry(?Region $country)
    {
        $this->country = $country;
    }

	public function getTelevisionSerieBiographies()
	{
		return $this->televisionSerieBiographies;
	}

	public function setTelevisionSerieBiographies($televisionSerieBiographies)
	{
		$this->televisionSerieBiographies = $televisionSerieBiographies;
	}

    public function setIntroduction($introduction)
    {
        $this->introduction = $introduction;
    }

    public function getIntroduction()
    {
        return $this->introduction;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

    public function setIdentifiers($identifiers)
    {
        $this->identifiers = $identifiers;
    }

    public function getIdentifiers()
    {
        return $this->identifiers;
    }
}