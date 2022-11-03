<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * App\Entity\BookEdition
 *
 * @ORM\Table(name="book_edition")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\BookEditionRepository")
 */
class BookEdition implements Interfaces\PhotoIllustrationInterface
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
     * @var string $subtitle
     *
     * @ORM\Column(type="string", nullable=true)
	 * @Groups("api_read")
     */
    private $subtitle;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\BookEditionBiography", mappedBy="bookEdition", cascade={"persist"})
     * @ORM\JoinTable(name="book_edition_biography",
     *      joinColumns={@ORM\JoinColumn(name="book_edition_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="biography_id", referencedColumnName="id", onDelete="cascade")}     
     *      )
	 */
	protected $biographies;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $illustration;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
	 * @Groups("api_read")
     */
    private $isbn10;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
	 * @Groups("api_read")
     */
    private $isbn13;

    /**
     * @ORM\Column(type="text", nullable=true)
	 * @Groups("api_read")
     */
    private $backCover;

    /**
     * @ORM\Column(type="integer", nullable=true)
	 * @Groups("api_read")
     */
    private $numberPage;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Publisher")
	 * @Groups("api_read")
     */
    protected $publisher;

    /**
     * @var string $format
     *
     * @ORM\Column(type="string", length=30)
	 * @Groups("api_read")
     */
    private $format;

    /**
     * @var date $publicationDate
     *
     * @ORM\Column(type="string", nullable=true)
	 * @Groups("api_read")
     */
    private $publicationDate;
	
    /**
    * @ORM\ManyToOne(targetEntity=Book::class, inversedBy="bookEditions")
	* @Groups("api_read")
    */
	protected $book;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $wholeBook;
	
	public function __toString(): string
	{
		return $this->book->getTitle()." - ".$this->isbn13;
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
     * Add biographies
     *
     * @param App\Entity\Biography $biographies
     */
	public function addBiography(Biography $biography)
	{
		$this->biographies[] = $biography;
	}

    /**
     * Set Biographies
     *
     * @param string $Biographies
     */
    public function setBiographies($biographies)
    {
        $this->biographies = $biographies;
    }

    /**
     * Remove biographies
     *
     * @param App\Entity\Biography $biographies
     */
	public function removeBiography(Biography $biography)
	{
		$this->biographies->removeElement(biography);
	}

    /**
     * Get biographies
     *
     * @return Doctrine\Common\Collections\Collection
     */
	public function getBiographies()
	{
		return $this->biographies;
	}

    public function getPublisher()
    {
        return $this->publisher;
    }

    public function setPublisher(?Publisher $publisher)
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
     * Set backCover
     *
     * @param string $backCover
     */
    public function setBackCover($backCover)
    {
        $this->backCover = $backCover;
    }

    /**
     * Get backCover
     *
     * @return string 
     */
    public function getBackCover()
    {
        return $this->backCover;
    }

    /**
     * Set publicationDate
     *
     * @param string $publicationDate
     */
    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
    }

    /**
     * Get publicationDate
     *
     * @return string 
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    /**
     * Set book
     *
     * @param Book $book
     */
    public function setBook($book)
    {
        $this->book = $book;
    }

    /**
     * Get book
     *
     * @return Book 
     */
    public function getBook()
    {
        return $this->book;
    }


    /**
     * Set wholeBook
     *
     * @param string $wholeBook
     */
    public function setWholeBook($wholeBook)
    {
        $this->wholeBook = $wholeBook;
    }

    /**
     * Get wholeBook
     *
     * @return string 
     */
    public function getWholeBook()
    {
        return $this->wholeBook;
    }
	
	public function getFullPdfPath() {
        return null === $this->wholeBook ? null : $this->getUploadRootPdfDir(). $this->wholeBook;
    }

    public function getUploadRootPdfDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootPdfDir();
    }

	public function getAssetPdfPath()
	{
		return "extended/photo/bookedition/pdf/";
	}

    public function getTmpUploadRootPdfDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetPdfPath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadWholeBook() {
        // the file property can be empty if the field is not required
        if (null === $this->wholeBook)
            return;

		if(is_object($this->wholeBook))
		{
			$NameFile = basename($this->wholeBook->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->wholeBook->move($this->getTmpUploadRootPdfDir(), $NewNameFile);
			}else{
				if (is_object($this->wholeBook))
					$this->wholeBook->move($this->getUploadRootPdfDir(), $NewNameFile);
			}
			if (is_object($this->wholeBook))
				$this->setWholeBook($NewNameFile);
		} elseif(filter_var($this->wholeBook, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->wholeBook);
			$pi = pathinfo($this->wholeBook);
			$extension = $res = pathinfo(parse_url($this->wholeBook, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setWholeBook($filename);
		}
    }

    /**
     * Set subtitle
     *
     * @param string $subtitle
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * Get subtitle
     *
     * @return string 
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }
}