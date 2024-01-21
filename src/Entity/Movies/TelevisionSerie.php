<?php

namespace App\Entity\Movies;

use App\Entity\MappedSuperclassBase;
use App\Entity\Movies\GenreAudiovisual;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Region;
use App\Entity\FileManagement;

/**
 * App\Entity\TelevisionSerie
 *
 * @ORM\Table(name="televisionserie")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\TelevisionSerieRepository")
 */
class TelevisionSerie extends MappedSuperclassBase
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
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Movies\GenreAudiovisual")
     */
    protected $genre;
	
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Region")
     */
    protected $country;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\Movies\TelevisionSerieBiography", mappedBy="televisionSerie", cascade={"persist"})
     * @ORM\JoinTable(name="televisionserie_biography",
     *      joinColumns={@ORM\JoinColumn(name="televisionSerie_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="biography_id", referencedColumnName="id", onDelete="cascade")}     
     *      )
	 */
	private $televisionSerieBiographies;

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

    public function setIdentifiers($identifiers)
    {
        $this->identifiers = $identifiers;
    }

    public function getIdentifiers()
    {
        return $this->identifiers;
    }
}