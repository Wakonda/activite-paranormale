<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ausi\SlugGenerator\SlugGenerator;

#[ORM\Table(name: 'page')]
#[ORM\Entity(repositoryClass: 'App\Repository\PageRepository')]
class Page
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

	#[ORM\Column(type: 'string', length: 255)]
	protected $title;

	#[ORM\Column(type: 'text', nullable: true)]
	protected $text;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\State')]
    protected $state;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Licence')]
    protected $licence;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    protected $language;

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

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
    private $internationalName;

	public function __construct()
	{
		$this->writingDate = new \DateTime();
		$this->publicationDate = new \DateTime();
	}

	public function getAssetImagePath()
	{
		return "extended/photo/page/";
	}

    public function getId()
    {
        return $this->id;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
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

    public function setAuthor(User $author)
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
}