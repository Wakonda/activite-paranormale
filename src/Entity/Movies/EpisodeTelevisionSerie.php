<?php

namespace App\Entity\Movies;

use App\Entity\MappedSuperclassBase;
use App\Entity\Movies\GenreAudiovisual;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Country;

/**
 * App\Entity\Movie
 *
 * @ORM\Table(name="episodetelevisionserie")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeTelevisionSerieRepository")
 */
class EpisodeTelevisionSerie
{
	use \App\Entity\GenericEntityTrait;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
	
    /**
     * @var string $title
     *
     * @ORM\Column(type="string", length=255)
     */
    private $title;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $synopsis;

    /**
     * @var integer $duration
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var integer $season
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $season;

    /**
     * @var integer $episodeNumber
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $episodeNumber;
	
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Movies\TelevisionSerie")
     */
    protected $televisionSerie;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $releaseDate;

    /**
     * @var text $source
     *
     * @ORM\Column(name="source", type="text", nullable=true)
     */
    private $source;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Movies\TelevisionSerieBiography", mappedBy="episodeTelevisionSerie", cascade={"persist"})
     * @ORM\JoinTable(name="televisionserie_biography",
     *      joinColumns={@ORM\JoinColumn(name="episodetelevisionserie_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="biography_id", referencedColumnName="id", onDelete="cascade")}     
     *      )
	 */
	private $episodeTelevisionSerieBiographies;

	/**
	 * @var string $wikidata
	 *
	 * @ORM\Column(name="wikidata", type="string", length=15, nullable=true)
	 */
	private $wikidata;

    /**
     * @ORM\Column(name="fullStreaming", type="text", nullable=true)
     */
    private $fullStreaming;
	
	public function getLanguage()
	{
		return $this->televisionSerie->getLanguage();
	}
	
	public function getTheme()
	{
		return $this->televisionSerie->getTheme();
	}
	
	public function getPublicationDate()
	{
		return $this->televisionSerie->getPublicationDate();
	}

	public function getUrlSlug()
	{
		return $this->title;
	}
	
	public function getSubTitle(): string {
		return $this->televisionSerie->getTitle();
	}
	
	public function getShowRoute()
	{
		return "TelevisionSerie_Episode";
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

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(Country $country)
    {
        $this->country = $country;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set synopsis
     *
     * @param string $synopsis
     */
    public function setSynopsis($synopsis)
    {
        $this->synopsis = $synopsis;
    }

    /**
     * Get synopsis
     *
     * @return string 
     */
    public function getSynopsis()
    {
        return $this->synopsis;
    }

    /**
     * Set season
     *
     * @param integer $season
     */
    public function setSeason($season)
    {
        $this->season = $season;
    }

    /**
     * Get season
     *
     * @return integer
     */
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * Set episodeNumber
     *
     * @param integer $episodeNumber
     */
    public function setEpisodeNumber($episodeNumber)
    {
        $this->episodeNumber = $episodeNumber;
    }

    /**
     * Get episodeNumber
     *
     * @return integer
     */
    public function getEpisodeNumber()
    {
        return $this->episodeNumber;
    }

    public function setTelevisionSerie($televisionSerie)
    {
        $this->televisionSerie = $televisionSerie;
    }

    public function getTelevisionSerie()
    {
        return $this->televisionSerie;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setReleaseDate($releaseDate)
    {
        $this->releaseDate = $releaseDate;
    }

    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

	public function getEpisodeTelevisionSerieBiographies()
	{
		return $this->episodeTelevisionSerieBiographies;
	}

	public function setEpisodeTelevisionSerieBiographies($episodeTelevisionSerieBiographies)
	{
		$this->episodeTelevisionSerieBiographies = $episodeTelevisionSerieBiographies;
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
     * Set fullStreaming
     *
     * @param string $fullStreaming
     */
    public function setFullStreaming($fullStreaming)
    {
        $this->fullStreaming = $fullStreaming;
    }

    /**
     * Get fullStreaming
     *
     * @return string
     */
    public function getFullStreaming()
    {
        return $this->fullStreaming;
    }
}