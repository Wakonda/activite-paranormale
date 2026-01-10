<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Table(name: 'book_edition')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\BookEditionRepository')]
class BookEdition implements Interfaces\PhotoIllustrationInterface
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'string', nullable: true)]
	#[Groups('api_read')]
    private $subtitle;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $photo;

	#[ORM\OneToMany(targetEntity: BookEditionBiography::class, mappedBy: "bookEdition", cascade: ["persist"])]
	#[ORM\JoinTable(name: "book_edition_biography")]
	#[ORM\JoinColumn(name: "book_edition_id", referencedColumnName: "id", onDelete: "cascade")]
	#[ORM\InverseJoinColumn(name: "biography_id", referencedColumnName: "id", onDelete: "cascade")]
	protected $biographies;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(type: 'string', length: 15, nullable: true)]
	#[Groups('api_read')]
    private $isbn10;

	#[ORM\Column(type: 'string', length: 15, nullable: true)]
	#[Groups('api_read')]
    private $isbn13;

	#[ORM\Column(name: 'backCover', type: 'text', nullable: true)]
	#[Groups('api_read')]
    private $backCover;

	#[ORM\Column(name: 'numberPage', type: 'integer', nullable: true)]
	#[Groups('api_read')]
    private $numberPage;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Publisher')]
	#[Groups('api_read')]
    protected $publisher;

	#[ORM\Column(type: 'string', length: 30)]
	#[Groups('api_read')]
    private $format;

	#[ORM\Column(name: 'publicationDate', type: 'string', nullable: true)]
	#[Groups('api_read')]
    private $publicationDate;

	#[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'bookEditions')]
	#[Groups('api_read')]
	protected $book;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(name: 'wholeBook', type: 'string', length: 255, nullable: true)]
    private $wholeBook;
	
	public function __toString(): string
	{
		return $this->book->getTitle()." - ".$this->isbn13;
	}
	
	public function getLanguage() {
		return $this->book->getLanguage();
	}

    public function getId()
    {
        return $this->id;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
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

	public function addBiography($biography)
	{
		$this->biographies[] = $biography;
	}

    public function setBiographies($biographies)
    {
        $this->biographies = $biographies;
    }

	public function removeBiography(Biography $biography)
	{
		$this->biographies->removeElement(biography);
	}

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

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setBackCover($backCover)
    {
        $this->backCover = $backCover;
    }

    public function getBackCover()
    {
        return $this->backCover;
    }

    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
    }

    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    public function setBook($book)
    {
        $this->book = $book;
    }

    public function getBook()
    {
        return $this->book;
    }

    public function setWholeBook($wholeBook)
    {
        $this->wholeBook = $wholeBook;
    }

    public function getWholeBook()
    {
        return $this->wholeBook;
    }
	
	public function getFullPdfPath() {
        return null === $this->wholeBook ? null : $this->getUploadRootPdfDir(). $this->wholeBook;
    }

    public function getUploadRootPdfDir() {
        return $this->getTmpUploadRootPdfDir();
    }

	public function getAssetPdfPath()
	{
		return "extended/photo/bookedition/pdf/";
	}

    public function getTmpUploadRootPdfDir() {
        return __DIR__ . '/../../public/'.$this->getAssetPdfPath();
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadWholeBook() {
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

    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    public function getSubtitle()
    {
        return $this->subtitle;
    }
}