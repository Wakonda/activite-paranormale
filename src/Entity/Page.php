<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Ausi\SlugGenerator\SlugGenerator;

/**
 * App\Entity\President
 *
 * @ORM\Table(name="page")
 * @ORM\Entity(repositoryClass="App\Repository\PageRepository")
 */
class Page
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\Column(type="string", nullable=true) 
	 */
	protected $title;

	/**
	 * @ORM\Column(type="text", nullable=true)
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
     */
    protected $language;

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
    * @ORM\JoinColumn(name="history_id", referencedColumnName="id", onDelete="CASCADE")
    */
    protected $history;

	/**
     * @var string $internationalName
     *
     * @ORM\Column(name="internationalName", type="string", length=255)
     */
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

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
	
	/**
     * Set internationalName
     *
     * @param string $internationalName
     */
    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    /**
     * Get internationalName
     *
     * @return string 
     */
    public function getInternationalName()
    {
        return $this->internationalName;
    }
	
    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
}