<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'album')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\AlbumRepository')]
class Album
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\Column(type: 'text', nullable: true)]
	protected $text;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Artist')]
    private $artist;

	#[ORM\Column(name: 'releaseYear', type: 'string', length: 255)]
    private $releaseYear;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Licence')]
    protected $licence;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'identifiers', type: 'text', nullable: true)]
    private $identifiers;

	#[ORM\Column(name: 'reviewScores', type: 'text', nullable: true)]
    private $reviewScores;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;

	public function __toString(): string
	{
		return $this->title." - ".$this->artist->getTitle();
	}
	
	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
		if(!empty($title))
			$this->title = htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8');

		$this->setSlug();
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

    public function setArtist($artist)
    {
        $this->artist = $artist;
    }

    public function getArtist()
    {
        return $this->artist;
    }

    public function setReleaseYear($releaseYear)
    {
        $this->releaseYear = $releaseYear;
    }

    public function getReleaseYear()
    {
        return $this->releaseYear;
    }

	public function getLicence()
    {
        return $this->licence;
    }

    public function setLicence(Licence $licence)
    {
        $this->licence = $licence;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }

	public function getFullPicturePath() {
        return null === $this->image ? null : $this->getUploadRootDir(). $this->image;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/album/";
	}

    public function getTmpUploadRootDir() {
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

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function setText($text)
    {
		$this->text = $text;
    }

    public function getText()
    {
        return $this->text;
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

    public function setIdentifiers($identifiers)
    {
        $this->identifiers = $identifiers;
    }

    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    public function setReviewScores($reviewScores)
    {
        $this->reviewScores = $reviewScores;
    }

    public function getReviewScores()
    {
        return $this->reviewScores;
    }
}