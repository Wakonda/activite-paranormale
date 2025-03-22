<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Ausi\SlugGenerator\SlugGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Interfaces\SearchEngineInterface;

#[ORM\MappedSuperclass]
class MappedSuperclassBase implements SearchEngineInterface
{
	use \App\Entity\GenericEntityTrait;

	#[ORM\Column(type: 'string', length: 255)]
	protected $title;

	#[ORM\Column(type: 'text', nullable: true)]
	#[Groups('api_read')]
	protected $text;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\State')]
    protected $state;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Licence')]
    protected $licence;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
	#[Groups('api_read')]
    protected $language;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Theme')]
	#[Groups('api_read')]
    protected $theme;

	#[ORM\Column(name: 'writingDate', type: 'datetime')]
    protected $writingDate;

	#[ORM\Column(name: 'publicationDate', type: 'datetime')]
    protected $publicationDate;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
	#[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true)]
    protected $author;

	#[ORM\Column(name: 'isAnonymous', type: 'string', length: 1, nullable: true)]
    protected $isAnonymous;

	#[ORM\Column(name: 'pseudoUsed', type: 'string', length: 255, nullable: true)]
    protected $pseudoUsed;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;

	#[ORM\OneToOne(targetEntity: History::class, cascade: ['remove'])]
	#[ORM\JoinColumn(name: 'history_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected $history;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\Column(name: 'archive', type: 'boolean', nullable: true)]
    private $archive;

	#[ORM\Column(name: 'socialNetworkIdentifiers', type: 'json', nullable: true)]
    private $socialNetworkIdentifiers;

	public function __construct()
	{
		$this->writingDate = new \DateTime();
		$this->publicationDate = new \DateTime();
		$this->archive = false;
	}

	public function authorToString()
	{
		if($this->isAnonymous == 1) {
			if($this->pseudoUsed != "")
				return $this->pseudoUsed;

			return "Anonymous";
		}
		else {
			if($this->author == null)
				return $this->pseudoUsed;
			else
				return $this->author->getUsername();
		}
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
			$generator = new SlugGenerator;
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

    public function setText($text)
    {
		$this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

	public function getState()
    {
        return $this->state;
    }

    public function setState(State $state)
    {
        $this->state = $state;
    }

	public function getLicence()
    {
        return $this->licence;
    }

    public function setLicence(Licence $licence)
    {
        $this->licence = $licence;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme)
    {
        $this->theme = $theme;
    }

    public function setWritingDate($writingDate)
    {
        $this->writingDate = $writingDate;
    }

    public function getWritingDate()
    {
        return $this->writingDate;
    }

    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
    }

    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    public function setAuthor(?User $author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setIsAnonymous($isAnonymous)
    {
        $this->isAnonymous = $isAnonymous;
    }

    public function getIsAnonymous()
    {
        return $this->isAnonymous;
    }

    public function setPseudoUsed($pseudoUsed)
    {
        $this->pseudoUsed = $pseudoUsed;
    }

    public function getPseudoUsed()
    {
        return $this->pseudoUsed;
    }

    public function setHistory($history)
    {
        $this->history = $history;
    }

    public function getHistory()
    {
        return $this->history;
    }

    public function setArchive($archive)
    {
        $this->archive = $archive;
    }

    public function getArchive()
    {
        return $this->archive;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setSocialNetworkIdentifiers($socialNetworkIdentifiers)
    {
        $this->socialNetworkIdentifiers = $socialNetworkIdentifiers;
    }

    public function getSocialNetworkIdentifiers()
    {
        return $this->socialNetworkIdentifiers;
    }
}