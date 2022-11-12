<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Album
 *
 * @ORM\Table(name="album")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\AlbumRepository")
 */
class Album
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $text;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Artist")
     */
    private $artist;

    /**
     * @var integer $releaseYear
     *
     * @ORM\Column(name="releaseYear", type="string", length=255)
     */
    private $releaseYear;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Licence")
     */
    protected $licence;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    private $language;

    /**
     * @ORM\Column(name="source", type="text", nullable=true)
     */
    private $source;

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

	public function __toString(): string
	{
		return $this->title." - ".$this->artist->getTitle();
	}
	
	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
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
     * Set artist
     *
     * @param artist $artist
     */
    public function setArtist($artist)
    {
        $this->artist = $artist;
    }

    /**
     * Get artist
     *
     * @return artist 
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * Set releaseYear
     *
     * @param integer $releaseYear
     */
    public function setReleaseYear($releaseYear)
    {
        $this->releaseYear = $releaseYear;
    }

    /**
     * Get releaseYear
     *
     * @return integer 
     */
    public function getReleaseYear()
    {
        return $this->releaseYear;
    }

	public function getLicence()
    {
        return $this->licence;
    }

    public function setLicence(Licence $licence)
    {
        $this->licence = $licence;
    }

    /**
     * Set image
     *
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

	public function getFullPicturePath() {
        return null === $this->image ? null : $this->getUploadRootDir(). $this->image;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/album/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }
	
    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
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
     * Set text
     *
     * @param text $text
     */
    public function setText($text)
    {
		$this->text = $text;
    }

    /**
     * Get text
     *
     * @return text 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set source
     *
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set wikidata
     *
     * @param string $wikidata
     */
    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    /**
     * Get wikidata
     *
     * @return string 
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
}