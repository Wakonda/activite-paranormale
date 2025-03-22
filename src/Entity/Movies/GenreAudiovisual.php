<?php

namespace App\Entity\Movies;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Language;
use App\Entity\FileManagement;
use Ausi\SlugGenerator\SlugGenerator;

#[ORM\Table(name: 'genreaudiovisual')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\GenreAudiovisualRepository')]
class GenreAudiovisual
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $photo;

	#[ORM\OneToOne(targetEntity: 'App\Entity\FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(type: 'string', length: 255)]
    private $title;	

	#[ORM\Column(type: 'text', nullable: true)]
	protected $text;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
    private $internationalName;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;

	#[ORM\Column(name: 'fiction', type: 'boolean', nullable: true)]
	private $fiction;
	
	public function __toString()
	{
		return $this->title;
	}

	public function getUrlSlug() {
		return $this->slug;
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
		return "extended/photo/movie/genreaudiovisual/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../../public/'.$this->getAssetImagePath();
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
		$this->setSlug();
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setInternationalName(string $internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName(): ?string
    {
        return $this->internationalName;
    }

    public function setText(string $text)
    {
		$this->text = $text;
    }

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

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
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

    public function setFiction($fiction)
    {
        $this->fiction = $fiction;
    }

    public function getFiction()
    {
        return $this->fiction;
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
}