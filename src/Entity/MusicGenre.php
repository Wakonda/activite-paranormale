<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'musicgenre')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\MusicGenreRepository')]
class MusicGenre
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(name: 'text', type: 'text', nullable: true)]
    private $text;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;
	
	public function __clone() {
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

    public function getId() {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSlug()
    {
		if(empty($this->slug)) {
			$generator = new \Ausi\SlugGenerator\SlugGenerator;
			$this->slug = $generator->generate($this->title);
		}
    }

    public function getSlug()
    {
        return $this->slug;
    }

	public function getUrlSlug()
	{
		return !empty($this->slug) ? $this->slug : $this->title;
	}

	public function getFullPicturePath() {
        return null === $this->photo ? null : $this->getUploadRootDir(). $this->photo;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/music/genre/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
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
}