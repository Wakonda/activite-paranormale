<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;
use Symfony\Component\Serializer\Annotation\Groups;
use Ausi\SlugGenerator\SlugGenerator;

#[ORM\Table(name: 'biography')]
#[ORM\Entity(repositoryClass: 'App\Repository\BiographyRepository')]
class Biography implements Interfaces\PhotoIllustrationInterface
{
	use \App\Entity\GenericEntityTrait;
	
	const PERSON = "Person";
	const FICTIONAL_CHARACTER = "FictionalCharacter";
	const OTHER = "Other";
	
	const MALE_GENDER="male";
	const FEMALE_GENDER="female";

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
	#[Groups('api_read')]
    private $title;

	#[ORM\Column(name: 'text', type: 'text', nullable: true)]
	#[Groups('api_read')]
    private $text;

	#[ORM\Column(name: 'birthDate', type: 'string', length:12, nullable: true)]
	#[Groups('api_read')]
    private $birthDate;

	#[ORM\Column(name: 'deathDate', type: 'string', length:12, nullable: true)]
	#[Groups('api_read')]
    private $deathDate;

	#[ORM\Column(name: 'feastDay', type: 'string', length:12, nullable: true)]
	#[Groups('api_read')]
    private $feastDay;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Region')]
	#[Groups('api_read')]
    protected $nationality;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'kind', type: 'string', length: 255)]
	 private $kind;

	#[ORM\ManyToMany(targetEntity: 'App\Entity\Document', mappedBy: 'authorDocumentBiographies')]
    private $documents;

	#[ORM\ManyToMany(targetEntity: 'App\Entity\Book', mappedBy: 'authors')]
    private $books;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'links', type: 'text', nullable: true)]
    private $links;

	#[ORM\Column(name: 'identifiers', type: 'text', nullable: true)]
    private $identifiers;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;

	#[ORM\Column(name: 'gender', type: 'string', length: 100, nullable: true)]
    private $gender;

	public function __toString()
	{
		return $this->title;
	}

	#[Groups('api_read')]
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

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
		$this->setSlug();
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setText($text)
    {
        $this->text = $this->purifier($text);
    }

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
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/biography/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	public function getDocuments()
	{
		return $this->documents;
	}

	public function getBooks()
	{
		return $this->books;
	}

    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    public function getKind()
    {
        return $this->kind;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;
    }

    public function getBirthDate()
    {
        return $this->birthDate;
    }

    public function setDeathDate($deathDate)
    {
        $this->deathDate = $deathDate;
    }

    public function getDeathDate()
    {
        return $this->deathDate;
    }

    public function setFeastDay($feastDay)
    {
        $this->feastDay = $feastDay;
    }

    public function getFeastDay()
    {
        return $this->feastDay;
    }

    public function setNationality($nationality)
    {
        $this->nationality = $nationality;
    }

    public function getNationality()
    {
        return $this->nationality;
    }

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

    public function setLinks($links)
    {
        $this->links = $links;
    }

    public function getLinks(): ?string
    {
		if(empty($this->links))
			return null;

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