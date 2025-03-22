<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'artist')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\ArtistRepository')]
class Artist
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\MusicGenre')]
    private $genre;

	#[ORM\Column(name: 'website', type: 'string', length: 255, nullable: true)]
    private $website;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(name: 'biography', type: 'text', nullable: true)]
    private $biography;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\OneToMany(targetEntity: ArtistBiography::class, mappedBy: "artist", cascade: ["persist"])]
	#[ORM\JoinTable(
		name: "artist_biography",
		joinColumns: [new ORM\JoinColumn(name: "artist_id", referencedColumnName: "id", onDelete: "cascade")],
		inverseJoinColumns: [new ORM\JoinColumn(name: "biography_id", referencedColumnName: "id", onDelete: "cascade")]
	)]
	private $artistBiographies;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Region')]
    protected $country;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'identifiers', type: 'text', nullable: true)]
    private $identifiers;

	#[ORM\Column(name: 'socialNetwork', type: 'text', nullable: true)]
    private $socialNetwork;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;
	
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

    public function setGenre($genre)
    {
        $this->genre = $genre;
    }

    public function getGenre()
    {
        return $this->genre;
    }

    public function setWebsite($website)
    {
        $this->website = $website;
    }

    public function getWebsite()
    {
        return $this->website;
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
        return null === $this->photo ? null : $this->getUploadRootDir(). $this->photo;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/music/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    public function setBiography($biography)
    {
        $this->biography = $biography;
    }

    public function getBiography()
    {
        return $this->biography;
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

	public function getArtistBiographies()
	{
		return $this->artistBiographies;
	}

	public function setArtistBiographies($artistBiographies)
	{
		$this->artistBiographies = $artistBiographies;
	}

	public function getCountry()
	{
		return $this->country;
	}

	public function setCountry($country)
	{
		$this->country = $country;
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

    public function setSocialNetwork($socialNetwork)
    {
        $this->socialNetwork = $socialNetwork;
    }

    public function getSocialNetwork()
    {
        return $this->socialNetwork;
    }

	public function getSocialNetworkUsername(string $socialNetwork) {
		if(empty($this->socialNetwork))
			return null;
	
		$res = "";
		foreach(json_decode($this->socialNetwork, true) as $sn) {
			if(!empty($sn["url"]) and strtolower($sn["socialNetwork"]) == strtolower($socialNetwork))
				$res = "@".ltrim(parse_url($sn["url"], PHP_URL_PATH), "@/");
		}

		return $res;
	}
}