<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Ausi\SlugGenerator\SlugGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\Interfaces\SearchEngineInterface;

/** @ORM\MappedSuperclass */
class MappedSuperclassBase implements SearchEngineInterface
{
	use \App\Entity\GenericEntityTrait;

	/**
	 * @ORM\Column(type="string", nullable=true) 
	 */
	protected $title;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 * @Groups("api_read")
	 */
	protected $text;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\State")
     */
    protected $state;
	
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Licence")
     */
    protected $licence;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
	 * @Groups("api_read")
     */
    protected $language;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Theme")
	 * @Groups("api_read")
     */
    protected $theme;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $writingDate;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $publicationDate;

    /**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User")
	 * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=true)
     */
    protected $author;

    /**
     * @ORM\Column(name="isAnonymous", type="string", length=1, nullable=true)
     */
    protected $isAnonymous;
	
	/**
     * @ORM\Column(name="pseudoUsed", type="string", length=255, nullable=true)
     */
    protected $pseudoUsed;

	/**
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    protected $slug;

    /**
    * @ORM\OneToOne(targetEntity=History::class, cascade={"remove"})
    * @ORM\JoinColumn(name="history_id", referencedColumnName="id", onDelete="SET NULL")
    */
    protected $history;

    /**
     * @ORM\Column(name="source", type="text", nullable=true)
     */
    private $source;

    /**
     * @ORM\Column(name="archive", type="boolean", nullable=true)
     */
    private $archive;

    /**
     * @ORM\Column(name="socialNetworkIdentifiers", type="json", nullable=true)
     */
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

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
		if(!empty($title))
			$this->title = htmlspecialchars($title, ENT_NOQUOTES, 'UTF-8');

		$this->setSlug();
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }
	
    /**
     * Set slug
     *
     * @param string $slug
     */
    public function setSlug()
    {
		if(empty($this->slug)) {
			$generator = new SlugGenerator;
			$this->slug = $generator->generate($this->title);
		}
    }

    /**
     * Get slug
     *
     * @return string 
     */
    public function getSlug()
    {
        return $this->slug;
    }
	
	public function getUrlSlug()
	{
		return !empty($this->slug) ? $this->slug : $this->title;
	}

    /**
     * Set text
     *
     * @param text $text
     */
    public function setText($text)
    {
		$this->text = $text;
    }

    /**
     * Get text
     *
     * @return text 
     */
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

    /**
     * Set writingDate
     *
     * @param datetime $writingDate
     */
    public function setWritingDate($writingDate)
    {
        $this->writingDate = $writingDate;
    }

    /**
     * Get writingDate
     *
     * @return datetime
     */
    public function getWritingDate()
    {
        return $this->writingDate;
    }

    /**
     * Set publicationDate
     *
     * @param date $publicationDate
     */
    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
    }

    /**
     * Get publicationDate
     *
     * @return date
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    /**
     * Set author
     *
     * @param App\Entity\User $author
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    /**
     * Get author
     *
     * @return App\Entity\User 
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set isAnonymous
     *
     * @param string $isAnonymous
     */
    public function setIsAnonymous($isAnonymous)
    {
        $this->isAnonymous = $isAnonymous;
    }

    /**
     * Get isAnonymous
     *
     * @return string 
     */
    public function getIsAnonymous()
    {
        return $this->isAnonymous;
    }

    /**
     * Set pseudoUsed
     *
     * @param string $pseudoUsed
     */
    public function setPseudoUsed($pseudoUsed)
    {
        $this->pseudoUsed = $pseudoUsed;
    }

    /**
     * Get pseudoUsed
     *
     * @return string 
     */
    public function getPseudoUsed()
    {
        return $this->pseudoUsed;
    }

    /**
     * Set history
     *
     * @param Hstory $history
     */
    public function setHistory($history)
    {
        $this->history = $history;
    }

    /**
     * Get history
     *
     * @return History 
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Set archive
     *
     * @param boolean $archive
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;
    }

    /**
     * Get archive
     *
     * @return boolean 
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Set source
     *
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set socialNetworkIdentifiers
     *
     * @param string $socialNetworkIdentifiers
     */
    public function setSocialNetworkIdentifiers($socialNetworkIdentifiers)
    {
        $this->socialNetworkIdentifiers = $socialNetworkIdentifiers;
    }

    /**
     * Get socialNetworkIdentifiers
     *
     * @return string 
     */
    public function getSocialNetworkIdentifiers()
    {
        return $this->socialNetworkIdentifiers;
    }
}