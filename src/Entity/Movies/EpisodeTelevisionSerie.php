<?php

namespace App\Entity\Movies;

use App\Entity\Movies\GenreAudiovisual;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Region;

#[ORM\Table(name: 'episodetelevisionserie')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\EpisodeTelevisionSerieRepository')]
class EpisodeTelevisionSerie
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'string', length: 255)]
    private $title;

	#[ORM\Column(type: 'text', nullable: true)]
	protected $synopsis;

	#[ORM\Column(type: 'integer', nullable: true)]
    private $duration;

	#[ORM\Column(type: 'integer', nullable: true)]
    private $season;

	#[ORM\Column(type: 'integer', nullable: true)]
    private $episodeNumber;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Movies\TelevisionSerie')]
    protected $televisionSerie;

	#[ORM\Column(type: 'date', nullable: true)]
    private $releaseDate;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\OneToMany(targetEntity: 'App\Entity\Movies\TelevisionSerieBiography', mappedBy: 'episodeTelevisionSerie', cascade: ['persist'])]
	#[ORM\JoinTable(name: 'televisionserie_biography', 
		joinColumns: [new ORM\JoinColumn(name: 'episodetelevisionserie_id', referencedColumnName: 'id', onDelete: 'cascade')],
		inverseJoinColumns: [new ORM\JoinColumn(name: 'biography_id', referencedColumnName: 'id', onDelete: 'cascade')]
	)]
	private $episodeTelevisionSerieBiographies;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'fullStreaming', type: 'text', nullable: true)]
    private $fullStreaming;

	#[ORM\Column(name: 'identifiers', type: 'text', nullable: true)]
    private $identifiers;
	
	public function getLanguage()
	{
		return $this->televisionSerie->getLanguage();
	}

	public function getTheme()
	{
		return $this->televisionSerie->getTheme();
	}

	public function getPublicationDate()
	{
		return $this->televisionSerie->getPublicationDate();
	}

	public function getUrlSlug()
	{
		return $this->title;
	}

	public function getSubTitle(): string {
		return $this->televisionSerie->getTitle();
	}
	
	public function getShowRoute()
	{
		return "TelevisionSerie_Episode";
	}

    public function getId()
    {
        return $this->id;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function getCountry(): ?Region
    {
        return $this->country;
    }

    public function setCountry(Region $country)
    {
        $this->country = $country;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSynopsis($synopsis)
    {
        $this->synopsis = $synopsis;
    }

    public function getSynopsis()
    {
        return $this->synopsis;
    }

    public function setSeason($season)
    {
        $this->season = $season;
    }

    public function getSeason()
    {
        return $this->season;
    }

    public function setEpisodeNumber($episodeNumber)
    {
        $this->episodeNumber = $episodeNumber;
    }

    public function getEpisodeNumber()
    {
        return $this->episodeNumber;
    }

    public function setTelevisionSerie($televisionSerie)
    {
        $this->televisionSerie = $televisionSerie;
    }

    public function getTelevisionSerie()
    {
        return $this->televisionSerie;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setReleaseDate($releaseDate)
    {
        $this->releaseDate = $releaseDate;
    }

    public function getReleaseDate()
    {
        return $this->releaseDate;
    }

	public function getEpisodeTelevisionSerieBiographies()
	{
		return $this->episodeTelevisionSerieBiographies;
	}

	public function setEpisodeTelevisionSerieBiographies($episodeTelevisionSerieBiographies)
	{
		$this->episodeTelevisionSerieBiographies = $episodeTelevisionSerieBiographies;
	}

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

    public function setFullStreaming($fullStreaming)
    {
        $this->fullStreaming = $fullStreaming;
    }

    public function getFullStreaming()
    {
        return $this->fullStreaming;
    }

    public function setIdentifiers($identifiers)
    {
        $this->identifiers = $identifiers;
    }

    public function getIdentifiers()
    {
        return $this->identifiers;
    }
}