<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Language;
use App\Entity\FileManagement;

/**
 * App\Entity\LiteraryGenre
 *
 * @ORM\Table(name="literarygenre")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\LiteraryGenreRepository")
 */
class LiteraryGenre
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
     * @ORM\OneToOne(targetEntity="App\Entity\FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;
	
    /**
     * @var string $title
     *
     * @ORM\Column(type="string", length=255)
     */
    private $title;	

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $text;

	/**
     * @var string $internationalName
     *
     * @ORM\Column(name="internationalName", type="string", length=255)
     */
    private $internationalName;

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
	 * @var string $fiction
	 *
	 * @ORM\Column(name="fiction", type="boolean", nullable=true)
	 */
	private $fiction;
	
	public function __construct()
	{
		$this->fiction = false;
	}
	
	public function __toString()
	{
		return $this->title;
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

	public function getAssetImagePath()
	{
		return "extended/photo/book/literarygenre/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

	/**
     * Set internationalName
     *
     * @param string $internationalName
     */
    public function setInternationalName(string $internationalName)
    {
        $this->internationalName = $internationalName;
    }

    /**
     * Get internationalName
     *
     * @return string 
     */
    public function getInternationalName(): ?string
    {
        return $this->internationalName;
    }

    /**
     * Set text
     *
     * @param text $text
     */
    public function setText(string $text)
    {
		$this->text = $text;
    }

    /**
     * Get text
     *
     * @return text 
     */
    public function getText(): ?string
    {
        return $this->text;
    }


    public function getLanguage(): ?Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
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
     * Set fiction
     *
     * @param boolean $fiction
     */
    public function setFiction($fiction)
    {
        $this->fiction = $fiction;
    }

    /**
     * Get fiction
     *
     * @return boolean
     */
    public function getFiction()
    {
        return $this->fiction;
    }
}