<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * App\Entity\Book
 *
 * @ORM\Table(name="book")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\BookRepository")
 */
class Book extends MappedSuperclassBase implements Interfaces\StoreInterface,  Interfaces\BiographyInterface
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
	 * Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Biography", inversedBy="books", cascade={"persist"})
     * @ORM\JoinTable(name="book_biography",
     *      joinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="biography_id", referencedColumnName="id", onDelete="cascade")}     
     *      )
	 * @Groups("api_read")
     */
	protected $authors;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Biography", cascade={"persist"})
     * @ORM\JoinTable(name="book_fictional_character_biography",
     *      joinColumns={@ORM\JoinColumn(name="book_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="biography_fictional_character_id", referencedColumnName="id", onDelete="cascade")}     
     *      )
	 * @Groups("api_read")
     */
	protected $fictionalCharacters;

	// Store
    /**
     * @var float $price
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * @var string $currencyPrice
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $currencyPrice;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $isbn10;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $isbn13;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $numberPage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $amazonCode;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Publisher")
     */
    protected $publisher;

    /**
     * @var string $format
     *
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $format;

    /**
     * @ORM\Column(name="store", type="text", nullable=true)
     */
    private $store;
	
	/**
     * @ORM\OneToMany(targetEntity=BookEdition::class, cascade={"persist", "remove"}, mappedBy="book")
     */
    protected $bookEditions;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\LiteraryGenre")
     */
    protected $genre;

	/**
	 * @var string $wikidata
	 *
	 * @ORM\Column(name="wikidata", type="string", length=15, nullable=true)
	 */
	private $wikidata;

	/**
	 * @var string $internationalName
	 *
	 * @ORM\Column(name="internationalName", type="string", length=255)
	 */
	private $internationalName;

	public function __construct()
	{
		parent::__construct();
	}
	
	const AUTHOR_OCCUPATION = "author";

	public static function getOccupations(): Array {
		return [
			self::AUTHOR_OCCUPATION
		];
	}

	public function getShowRoute()
	{
		return "Book_Show";
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
     * Set photo
     *
     * @param string $photo
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    /**
     * Get photo
     *
     * @return string 
     */
    public function getPhoto()
    {
        return $this->photo;
    }

	public function getFullPicturePath() {
        return null === $this->photo ? null : realpath($this->getUploadRootDir(). $this->photo);
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/book/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * Set price
     *
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * Get price
     *
     * @return float 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set currencyPrice
     *
     * @param string $currencyPrice
     */
    public function setCurrencyPrice($currencyPrice)
    {
        $this->currencyPrice = $currencyPrice;
    }

    /**
     * Get currencyPrice
     *
     * @return string 
     */
    public function getCurrencyPrice()
    {
        return $this->currencyPrice;
    }

    /**
     * Set isbn10
     *
     * @param string $isbn10
     */
    public function setIsbn10($isbn10)
    {
        $this->isbn10 = $isbn10;
    }

    /**
     * Get isbn10
     *
     * @return string 
     */
    public function getIsbn10()
    {
        return $this->isbn10;
    }

    /**
     * Set isbn13
     *
     * @param string $isbn13
     */
    public function setIsbn13($isbn13)
    {
        $this->isbn13 = $isbn13;
    }

    /**
     * Get isbn13
     *
     * @return string 
     */
    public function getIsbn13()
    {
        return $this->isbn13;
    }

    /**
     * Set numberPage
     *
     * @param integer $numberPage
     */
    public function setNumberPage($numberPage)
    {
        $this->numberPage = $numberPage;
    }

    /**
     * Get numberPage
     *
     * @return integer 
     */
    public function getNumberPage()
    {
        return $this->numberPage;
    }

    /**
     * Set amazonCode
     *
     * @param integer $amazonCode
     */
    public function setAmazonCode($amazonCode)
    {
        $this->amazonCode = $amazonCode;
    }

    /**
     * Get amazonCode
     *
     * @return integer 
     */
    public function getAmazonCode()
    {
        return $this->amazonCode;
    }

    /**
     * Add authors
     *
     * @param App\Entity\Biography $authors
     */
	public function addAuthor(Biography $biography)
	{
		$this->authors[] = $biography;
	}

    /**
     * Set authors
     *
     * @param string $authors
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;
    }

    /**
     * Remove authors
     *
     * @param App\Entity\Biography $authors
     */
	public function removeAuthor(Biography $biography)
	{
		$this->authors->removeElement($biography);
	}

    /**
     * Get authors
     *
     * @return Doctrine\Common\Collections\Collection
     */
	public function getAuthors()
	{
		return $this->authors;
	}

	public function addFictionalCharacter(Biography $biography)
	{
		$this->fictionalCharacters[] = $biography;
	}

    public function setFictionalCharacters($fictionalCharacters)
    {
        $this->fictionalCharacters = $fictionalCharacters;
    }

	public function removeFictionalCharacter(Biography $biography)
	{
		$this->fictionalCharacters->removeElement($biography);
	}

	public function getFictionalCharacters()
	{
		return $this->fictionalCharacters;
	}

    public function getPublisher()
    {
        return $this->publisher;
    }

    public function setPublisher(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Set format
     *
     * @param integer $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * Get format
     *
     * @return integer 
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set store
     *
     * @param string $store
     */
    public function setStore($store)
    {
        $this->store = $store;
    }

    /**
     * Get store
     *
     * @return string 
     */
    public function getStore()
    {
        return $this->store;
    }

    public function getBookEditions()
    {
        return $this->bookEditions;
    }
     
    public function addBookEdition(BookEdition $bookEdition)
    {
        $this->bookEditions->add($bookEdition);
        $bookEdition->setBook($this);
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

    public function setGenre($genre)
    {
        $this->genre = $genre;
    }

    public function getGenre()
    {
        return $this->genre;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function setIntroduction($introduction)
    {
        $this->introduction = $introduction;
    }

    public function getIntroduction()
    {
        return $this->introduction;
    }
}