<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;
use Symfony\Component\Serializer\Annotation\Groups;
use Ausi\SlugGenerator\SlugGenerator;

/**
 * App\Entity\Biography
 *
 * @ORM\Table(name="biography")
 * @ORM\Entity(repositoryClass="App\Repository\BiographyRepository")
 */
class Biography implements Interfaces\PhotoIllustrationInterface
{
	use \App\Entity\GenericEntityTrait;
	
	const PERSON = "Person";
	const FICTIONAL_CHARACTER = "FictionalCharacter";
	const OTHER = "Other";
	
	const MALE_GENDER="male";
	const FEMALE_GENDER="female";

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
	 * @Groups("api_read")
     */
    private $title;

    /**
     * @var text $text
     *
     * @ORM\Column(name="text", type="text", nullable=true)
	 * @Groups("api_read")
     */
    private $text;

    /**
     * @var date $birthDate
     *
     * @ORM\Column(name="birthDate", type="string", length=12, nullable=true)
	 * @Groups("api_read")
     */
    private $birthDate;

    /**
     * @var date $deathDate
     *
     * @ORM\Column(name="deathDate", type="string", length=12, nullable=true)
	 * @Groups("api_read")
     */
    private $deathDate;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Region")
	 * @Groups("api_read")
     */
    protected $nationality;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    private $language;

	/**
	 * @var string $kind
	 *
	 * @ORM\Column(name="kind", type="string", length=255)
	 */
	 private $kind;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Document", mappedBy="authorDocumentBiographies")
     */
    private $documents;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Book", mappedBy="authors")
     */
    private $books;

	/**
	 * @var string $internationalName
	 *
	 * @ORM\Column(name="internationalName", type="string", length=255)
	 */
	private $internationalName;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

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
     * @var text $links
     *
     * @ORM\Column(name="links", type="text", nullable=true)
     */
    private $links;

    /**
     * @ORM\Column(name="identifiers", type="text", nullable=true)
     */
    private $identifiers;

	/**
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    protected $slug;

    /**
     * @ORM\Column(name="gender", type="string", length=100, nullable=true)
     */
    private $gender;

	public function __toString()
	{
		return $this->title;
	}

	/**
	 * @Groups("api_read")
	 */
	public function getImgBase64(): ?string
	{
		if(!empty($this->illustration) and file_exists($f = realpath($this->getTmpUploadRootDir().$this->illustration->getRealNameFile())))
			return base64_encode(file_get_contents($f));

		return null;
	}

	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

	public function getShowRoute()
	{
		return "Biography_Show";
	}

	public function getUrlSlug() {
		return $this->slug;
	}

	public function getBirthDateToArray() {
		$isBC = str_starts_with($this->birthDate, "-");
		$date = explode("-", trim($this->birthDate, "-"));
		
		return [
			"day" => (isset($date[2]) and !empty($date[2])) ? intval($date[2]) : null,
			"month" => (isset($date[1]) and !empty($date[1])) ? intval($date[1]) : null,
			"year" => (isset($date[0]) and !empty($date[0])) ? ($isBC ? "-" : "").intval($date[0]) : null, 
		];
	}

	public function getDeathDateToArray() {
		$isBC = str_starts_with($this->deathDate, "-");
		$date = explode("-", trim($this->deathDate, "-"));
		
		return [
			"day" => (isset($date[2]) and !empty($date[2])) ? intval($date[2]) : null,
			"month" => (isset($date[1]) and !empty($date[1])) ? intval($date[1]) : null,
			"year" => (isset($date[0]) and !empty($date[0])) ? ($isBC ? "-" : "").intval($date[0]) : null, 
		];
	}

	protected function purifier($text)
	{
		$purifier = new APPurifierHTML();
		return $purifier->purifier($text);
	}

	// KIND
	public function isPerson()
	{
		return $this->kind == self::PERSON;
	}

	public function isFictionalCharacter()
	{
		return $this->kind == self::FICTIONAL_CHARACTER;
	}
	
	public function isOther()
	{
		return $this->kind == self::OTHER;
	}
	
	public function isMale() {
		return $this->gender == self::MALE_GENDER;
	}
	
	public function isFemale() {
		return $this->gender == self::FEMALE_GENDER;
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
		$this->setSlug();
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
     * Set text
     *
     * @param text $text
     */
    public function setText($text)
    {
        $this->text = $this->purifier($text);
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

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/biography/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

  /**
    * Get documents
    *
    * @return Doctrine\Common\Collections\Collection
    */
	public function getDocuments()
	{
		return $this->documents;
	}

  /**
    * Get books
    *
    * @return Doctrine\Common\Collections\Collection
    */
	public function getBooks()
	{
		return $this->books;
	}

    /**
     * Set kind
     *
     * @param string $kind
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    /**
     * Get kind
     *
     * @return kind 
     */
    public function getKind()
    {
        return $this->kind;
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
     * Set birthDate
     *
     * @param date $birthDate
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;
    }

    /**
     * Get birthDate
     *
     * @return date
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * Set deathDate
     *
     * @param date $deathDate
     */
    public function setDeathDate($deathDate)
    {
        $this->deathDate = $deathDate;
    }

    /**
     * Get deathDate
     *
     * @return date
     */
    public function getDeathDate()
    {
        return $this->deathDate;
    }

    public function setNationality($nationality)
    {
        $this->nationality = $nationality;
    }

    public function getNationality()
    {
        return $this->nationality;
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
     * Set links
     *
     * @param String $links
     */
    public function setLinks($links)
    {
        $this->links = $links;
    }

    /**
     * Get links
     *
     * @return String
     */
    public function getLinks(): ?string
    {
		if(empty(array_filter(array_column(!empty($d = json_decode($this->links, true)) ? $d : [], "url"))))
			return null;

        return $this->links;
    }

    public function setIdentifiers($identifiers)
    {
        $this->identifiers = $identifiers;
    }

    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    public function setSlug()
    {
		if(empty($this->slug))
			$this->slug = (new SlugGenerator)->generate($this->title);
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setGender($gender)
    {
		$this->gender = $gender;
    }

    public function getGender()
    {
        return $this->gender;
    }
}