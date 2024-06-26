<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\WebDirectory
 *
 * @ORM\Table(name="webdirectory")
 * @ORM\Entity(repositoryClass="App\Repository\WebDirectoryRepository")
 */
class WebDirectory
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
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string $link
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     */
    private $link;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, nullable=true)
     */
    private $logo;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

   /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    private $language;

   /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    private $websiteLanguage;

    /**
     * @var text $socialNetwork
     *
     * @ORM\Column(name="socialNetwork", type="text", nullable=true)
     */
    private $socialNetwork;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $text;
	
	/**
	 * @ORM\Column(type="string", nullable=true, length=10)
	 */
	private $foundedYear;
	
	/**
	 * @ORM\Column(type="string", nullable=true, length=10)
	 */
	private $defunctYear;

    /**
     * @var text $source
     *
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Licence")
     */
    protected $licence;

	/**
	 * @var string $internationalName
	 *
	 * @ORM\Column(name="internationalName", type="string", length=255)
	 */
	private $internationalName;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\State")
     */
    protected $state;

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
     * Set link
     *
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set logo
     *
     * @param string $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * Get logo
     *
     * @return string 
     */
    public function getLogo()
    {
        return $this->logo;
    }

	public function getFullPicturePath() {
        return null === $this->logo ? null : $this->getUploadRootDir(). $this->logo;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/webdirectory/";
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
     * Set socialNetwork
     *
     * @param text $socialNetwork
     */
    public function setSocialNetwork($socialNetwork)
    {
        $this->socialNetwork = $socialNetwork;
    }

    /**
     * Get socialNetwork
     *
     * @return text 
     */
    public function getSocialNetwork()
    {
        return $this->socialNetwork;
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
     * Set integer
     *
     * @param integer $foundedYear
     */
    public function setFoundedYear(?string $foundedYear)
    {
		$this->foundedYear = $foundedYear;
    }

    /**
     * Get foundedYear
     *
     * @return foundedYear
     */
    public function getFoundedYear()
    {
        return $this->foundedYear;
    }

    /**
     * Set integer
     *
     * @param integer $defunctYear
     */
    public function setDefunctYear(?string $defunctYear)
    {
		$this->defunctYear = $defunctYear;
    }

    /**
     * Get defunctYear
     *
     * @return defunctYear
     */
    public function getDefunctYear()
    {
        return $this->defunctYear;
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

	public function getLicence()
    {
        return $this->licence;
    }

    public function setLicence(?Licence $licence)
    {
        $this->licence = $licence;
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
	
    public function getWebsiteLanguage()
    {
        return $this->websiteLanguage;
    }

    public function setWebsiteLanguage(Language $websiteLanguage)
    {
        $this->websiteLanguage = $websiteLanguage;
    }

	public function getState()
    {
        return $this->state;
    }

    public function setState(State $state)
    {
        $this->state = $state;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }
}