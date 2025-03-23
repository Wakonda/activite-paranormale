<?php

namespace App\Entity\Movies;

use App\Entity\MappedSuperclassBase;
use App\Entity\Movies\GenreAudiovisual;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Region;
use App\Entity\FileManagement;

#[ORM\Table(name: 'movie')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\MovieRepository')]
class Movie extends MappedSuperclassBase
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

	#[ORM\Column(type: 'integer', nullable: true)]
    private $duration;

	#[ORM\Column(name: 'releaseYear', type: 'string', nullable: true)]
    private $releaseYear;

	#[ORM\Column(type: 'text', nullable: true)]
    private $trailer;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Movies\GenreAudiovisual')]
    protected $genre;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Region')]
    protected $country;

	#[ORM\OneToMany(targetEntity: MovieBiography::class, mappedBy: "movie", cascade: ["persist"])]
	#[ORM\JoinTable(
		name: "movie_biography",
		joinColumns: [new ORM\JoinColumn(name: "movie_id", referencedColumnName: "id", onDelete: "cascade")],
		inverseJoinColumns: [new ORM\JoinColumn(name: "biography_id", referencedColumnName: "id", onDelete: "cascade")]
	)]
	private $movieBiographies;

	#[ORM\ManyToOne(targetEntity: 'Movie')]
    private $previous;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'identifiers', type: 'text', nullable: true)]
    private $identifiers;

	#[ORM\Column(name: 'reviewScores', type: 'text', nullable: true)]
    private $reviewScores;

	#[ORM\Column(name: 'boxOffice', type: 'integer', nullable: true)]
    private $boxOffice;

	#[ORM\Column(name: 'boxOfficeUnit', type: 'string', length: 10, nullable: true)]
    private $boxOfficeUnit;

	#[ORM\Column(name: 'cost', type: 'integer', nullable: true)]
    private $cost;

	#[ORM\Column(name: 'costUnit', type: 'string', length: 10, nullable: true)]
    private $costUnit;

	#[ORM\Column(name: 'fullStreaming', type: 'text', nullable: true)]
    private $fullStreaming;

	public function __construct()
	{
		parent::__construct();
	}

	public function getShowRoute()
	{
		return "Movie_Show";
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
		return "extended/photo/movie/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../../public/'.$this->getAssetImagePath();
    }
	
	public function __toString(): string
	{
		return $this->title." - ".$this->releaseYear;
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

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setTrailer($trailer)
    {
        $this->trailer = $trailer;
    }

    public function getTrailer()
    {
        return $this->trailer;
    }

    public function setReleaseYear($releaseYear)
    {
        $this->releaseYear = $releaseYear;
    }

    public function getReleaseYear()
    {
        return $this->releaseYear;
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

	public function getMovieBiographies()
	{
		return $this->movieBiographies;
	}

	public function setMovieBiographies($movieBiographies)
	{
		$this->movieBiographies = $movieBiographies;
	}

    public function setIntroduction($introduction)
    {
        $this->introduction = $introduction;
    }

    public function getIntroduction()
    {
        return $this->introduction;
    }

    public function setPrevious($previous)
    {
        $this->previous = $previous;
    }

    public function getPrevious()
    {
        return $this->previous;
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

    public function setReviewScores($reviewScores)
    {
        $this->reviewScores = $reviewScores;
    }

    public function getReviewScores()
    {
        return $this->reviewScores;
    }

    public function setBoxOffice($boxOffice)
    {
        $this->boxOffice = $boxOffice;
    }

    public function getBoxOffice()
    {
        return $this->boxOffice;
    }

    public function setBoxOfficeUnit($boxOfficeUnit)
    {
        $this->boxOfficeUnit = $boxOfficeUnit;
    }

    public function getBoxOfficeUnit()
    {
        return $this->boxOfficeUnit;
    }

    public function setCost($cost)
    {
        $this->cost = $cost;
    }

    public function getCost()
    {
        return $this->cost;
    }

    public function setCostUnit($costUnit)
    {
        $this->costUnit = $costUnit;
    }

    public function getCostUnit()
    {
        return $this->costUnit;
    }

    public function setFullStreaming($fullStreaming)
    {
        $this->fullStreaming = $fullStreaming;
    }

    public function getFullStreaming()
    {
        return $this->fullStreaming;
    }
}