<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'book')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\BookRepository')]
class Book extends MappedSuperclassBase implements Interfaces\StoreInterface
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'text', nullable: true)]
	protected $introduction;

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $photo;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

    #[ORM\ManyToMany(targetEntity: "App\Entity\Biography", inversedBy: "books", cascade: ["persist"])]
    #[ORM\JoinTable(name: "book_biography")]
    #[ORM\JoinColumn(name: "book_id", referencedColumnName: "id", onDelete: "cascade")]
    #[ORM\InverseJoinColumn(name: "biography_id", referencedColumnName: "id", onDelete: "cascade")]
	#[Groups('api_read')]
	protected $authors;

    #[ORM\OneToMany(targetEntity: "App\Entity\BookEditionBiography", mappedBy: "book", cascade: ["persist"])]
    #[ORM\JoinTable(name: "book_edition_biography")]
    #[ORM\JoinColumn(name: "book_id", referencedColumnName: "id", onDelete: "cascade")]
    #[ORM\InverseJoinColumn(name: "biography_id", referencedColumnName: "id", onDelete: "cascade")]
	protected $biographies;

    #[ORM\ManyToMany(targetEntity: "App\Entity\Biography", cascade: ["persist"])]
    #[ORM\JoinTable(name: "book_fictional_character_biography")]
    #[ORM\JoinColumn(name: "book_id", referencedColumnName: "id", onDelete: "cascade")]
    #[ORM\InverseJoinColumn(name: "biography_fictional_character_id", referencedColumnName: "id", onDelete: "cascade")]
	#[Groups('api_read')]
	protected $fictionalCharacters;

	// Store
	#[ORM\Column(type: 'float', nullable: true)]
    private $price;

	#[ORM\Column(name: 'currencyPrice', type: 'string', length: 10, nullable: true)]
    private $currencyPrice;

	#[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $isbn10;

	#[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $isbn13;

	#[ORM\Column(name: 'numberPage', type: 'integer', nullable: true)]
    private $numberPage;

	#[ORM\Column(name: 'amazonCode', type: 'string', length: 255, nullable: true)]
    private $amazonCode;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Publisher')]
    protected $publisher;

	#[ORM\Column(type: 'string', length: 30, nullable: true)]
    private $format;

	#[ORM\Column(name: 'store', type: 'text', nullable: true)]
    private $store;

	#[ORM\OneToMany(targetEntity: BookEdition::class, cascade: ['persist', 'remove'], mappedBy: 'book')]
    protected $bookEditions;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\LiteraryGenre')]
    protected $genre;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	public function __construct()
	{
		parent::__construct();
	}

	public function getShowRoute()
	{
		return "Book_Show";
	}

    public function getId()
    {
        return $this->id;
    }

    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

	public function getFullPicturePath() {
        return null === $this->photo ? null : realpath($this->getUploadRootDir(). $this->photo);
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/book/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setCurrencyPrice($currencyPrice)
    {
        $this->currencyPrice = $currencyPrice;
    }

    public function getCurrencyPrice()
    {
        return $this->currencyPrice;
    }

    public function setIsbn10($isbn10)
    {
        $this->isbn10 = $isbn10;
    }

    public function getIsbn10()
    {
        return $this->isbn10;
    }

    public function setIsbn13($isbn13)
    {
        $this->isbn13 = $isbn13;
    }

    public function getIsbn13()
    {
        return $this->isbn13;
    }

    public function setNumberPage($numberPage)
    {
        $this->numberPage = $numberPage;
    }

    public function getNumberPage()
    {
        return $this->numberPage;
    }

    public function setAmazonCode($amazonCode)
    {
        $this->amazonCode = $amazonCode;
    }

    public function getAmazonCode()
    {
        return $this->amazonCode;
    }

	public function addAuthor(Biography $biography)
	{
		$this->authors[] = $biography;
	}

    public function setAuthors($authors)
    {
        $this->authors = $authors;
    }

	public function removeAuthor(Biography $biography)
	{
		$this->authors->removeElement($biography);
	}

	public function getAuthors()
	{
		$datas = [];

		foreach($this->biographies as $biography)
			$datas[] = $biography->getBiography();

		return $datas;
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

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setStore($store)
    {
        $this->store = $store;
    }

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

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

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

	public function addBiography($biography)
	{
		$this->biographies[] = $biography;
	}

    public function setBiographies($biographies)
    {
        $this->biographies = $biographies;
    }

	public function removeBiography($biography)
	{
		$this->biographies->removeElement($biography);
	}

	public function getBiographies()
	{
		return $this->biographies;
	}
}