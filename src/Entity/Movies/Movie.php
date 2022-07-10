<?php

namespace App\Entity\Movies;

use App\Entity\MappedSuperclassBase;
use App\Entity\Movies\GenreAudiovisual;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Country;
use App\Entity\FileManagement;

/**
 * App\Entity\Movie
 *
 * @ORM\Table(name="movie")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\MovieRepository")
 */
class Movie extends MappedSuperclassBase
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $introduction;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $illustration;

    /**
     * @var integer $duration
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var integer $releaseYear
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $releaseYear;

    /**
     * @var text $trailer
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $trailer;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Movies\GenreAudiovisual")
     */
    protected $genre;
	
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Country")
     */
    protected $country;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Movies\MovieBiography", mappedBy="movie", cascade={"persist"})
     * @ORM\JoinTable(name="movie_biography",
     *      joinColumns={@ORM\JoinColumn(name="movie_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="biography_id", referencedColumnName="id", onDelete="cascade")}     
     *      )
	 */
	private $movieBiographies;

    /**
     * @ORM\OneToOne(targetEntity="Movie")
     */
    private $previous;

	/**
	 * @var string $internationalName
	 *
	 * @ORM\Column(name="internationalName", type="string", length=255)
	 */
	private $internationalName;

	/**
	 * @var string $wikidata
	 *
	 * @ORM\Column(name="wikidata", type="string", length=15, nullable=true)
	 */
	private $wikidata;

    /**
     * @ORM\Column(name="identifiers", type="text", nullable=true)
     */
    private $identifiers;

    /**
     * @ORM\Column(name="reviewScores", type="text", nullable=true)
     */
    private $reviewScores;

    /**
     * @ORM\Column(name="boxOffice", type="integer", nullable=true)
     */
    private $boxOffice;

    /**
     * @ORM\Column(name="boxOfficeUnit", type="string", length=10, nullable=true)
     */
    private $boxOfficeUnit;

    /**
     * @ORM\Column(name="cost", type="integer", nullable=true)
     */
    private $cost;

    /**
     * @ORM\Column(name="costUnit", type="string", length=10, nullable=true)
     */
    private $costUnit;

	public function __construct()
	{
		parent::__construct();
	}

	public function getShowRoute()
	{
		return "Movie_Show";
	}

    /**
     * Get id
     *
     * @return integer 
     */
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
		if($this->illustration)
			$this->illustration = clone $this->illustration;
	}

    /**
     * Set illustration
     *
     * @param string $illustration
     */
    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    /**
     * Get illustration
     *
     * @return string 
     */
    public function getIllustration()
    {
        return $this->illustration;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }

	/**
     * Set trailer
     *
     * @param string $trailer
     */
    public function setTrailer($trailer)
    {
        $this->trailer = $trailer;
    }

    /**
     * Get trailer
     *
     * @return string 
     */
    public function getTrailer()
    {
        return $this->trailer;
    }

	/**
     * Set releaseYear
     *
     * @param string $releaseYear
     */
    public function setReleaseYear($releaseYear)
    {
        $this->releaseYear = $releaseYear;
    }

    /**
     * Get releaseYear
     *
     * @return string 
     */
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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(Country $country)
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

    /**
     * Set introduction
     *
     * @param string $introduction
     */
    public function setIntroduction($introduction)
    {
        $this->introduction = $introduction;
    }

    /**
     * Get introduction
     *
     * @return string 
     */
    public function getIntroduction()
    {
        return $this->introduction;
    }

    /**
     * Set previous
     *
     * @param string $previous
     */
    public function setPrevious($previous)
    {
        $this->previous = $previous;
    }

    /**
     * Get previous
     *
     * @return string 
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * Set internationalName
     *
     * @param string $internationalName
     */
    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    /**
     * Get internationalName
     *
     * @return internationalName 
     */
    public function getInternationalName()
    {
        return $this->internationalName;
    }

    /**
     * Set wikidata
     *
     * @param String $wikidata
     */
    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    /**
     * Get wikidata
     *
     * @return String
     */
    public function getWikidata()
    {
        return $this->wikidata;
    }

    /**
     * Set identifiers
     *
     * @param string $identifiers
     */
    public function setIdentifiers($identifiers)
    {
        $this->identifiers = $identifiers;
    }

    /**
     * Get identifiers
     *
     * @return string 
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * Set reviewScores
     *
     * @param string $reviewScores
     */
    public function setReviewScores($reviewScores)
    {
        $this->reviewScores = $reviewScores;
    }

    /**
     * Get reviewScores
     *
     * @return string 
     */
    public function getReviewScores()
    {
        return $this->reviewScores;
    }

    /**
     * Set boxOffice
     *
     * @param integer $boxOffice
     */
    public function setBoxOffice($boxOffice)
    {
        $this->boxOffice = $boxOffice;
    }

    /**
     * Get boxOffice
     *
     * @return integer
     */
    public function getBoxOffice()
    {
        return $this->boxOffice;
    }

    /**
     * Set boxOfficeUnit
     *
     * @param string $boxOfficeUnit
     */
    public function setBoxOfficeUnit($boxOfficeUnit)
    {
        $this->boxOfficeUnit = $boxOfficeUnit;
    }

    /**
     * Get boxOfficeUnit
     *
     * @return string
     */
    public function getBoxOfficeUnit()
    {
        return $this->boxOfficeUnit;
    }

    /**
     * Set cost
     *
     * @param string $cost
     */
    public function setCost($cost)
    {
        $this->cost = $cost;
    }


    /**
     * Get cost
     *
     * @return integer
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set costUnit
     *
     * @param string $costUnit
     */
    public function setCostUnit($costUnit)
    {
        $this->costUnit = $costUnit;
    }

    /**
     * Get costUnit
     *
     * @return string
     */
    public function getCostUnit()
    {
        return $this->costUnit;
    }
}