<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\UsefulLink
 *
 * @ORM\Table(name="usefullink")
 * @ORM\Entity(repositoryClass="App\Repository\UsefulLinkRepository")
 */
class UsefulLink
{
	use \App\Entity\GenericEntityTrait;

	const DEVELOPMENT_FAMILY = "development";
	const RESOURCE_FAMILY = "resource";
	const TOOL_FAMILY = "tool";
	const USEFULLINK_FAMILY = "usefullink";
	const TECHNICAL_FAMILY = "technical";

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string $text
     *
     * @ORM\Column(name="text", type="text", nullable=true)
     */
    private $text;

    /**
     * @var string $links
     *
     * @ORM\Column(name="links", type="text", nullable=true)
     */
    private $links;

    /**
     * @var string $tags
     *
     * @ORM\Column(name="tags", type="json", nullable=true)
     */
    private $tags;

    /**
     * @var string $category
     *
     * @ORM\Column(name="category", type="string", length=100, nullable=true)
     */
    private $category;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    protected $language;
	
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Licence")
     */
    protected $licence;

	/**
	 * @var string $internationalName
	 *
	 * @ORM\Column(name="internationalName", type="string", length=255)
	 */
	private $internationalName;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Blog")
     */
	private $website;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;
	
	public function isDevelopment(): bool {
		return self::DEVELOPMENT_FAMILY == $this->category;
	}
	
	public function isTool(): bool {
		return self::TOOL_FAMILY == $this->category;
	}
	
	public function isUsefulLink(): bool {
		return self::USEFULLINK_FAMILY == $this->category;
	}
	
	public function isTechnical(): bool {
		return self::TECHNICAL_FAMILY == $this->category;
	}

	public function getAssetImagePath()
	{
		return "extended/photo/usefullink/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	public function getEntityName()
	{
		return get_called_class();
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
     * Set title
     *
     * @param string $title
     * @return UsefulLink
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
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
     * Set text
     *
     * @param string $text
     * @return UsefulLink
     */
    public function setText($text)
    {
        $this->text = $text;
    
        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set links
     *
     * @param string $links
     * @return UsefulLink
     */
    public function setLinks($links)
    {
        $this->links = $links;
    
        return $this;
    }

    /**
     * Get links
     *
     * @return string 
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Set tags
     *
     * @param string $tags
     * @return UsefulLink
     */
    public function setTags($tags)
    {
        $this->tags = json_decode($tags);
    
        return $this;
    }

    /**
     * Get tags
     *
     * @return string 
     */
    public function getTags()
    {
        return is_null($this->tags) ? [] : json_encode($this->tags);
    }

    /**
     * Set category
     *
     * @param string $category
     * @return UsefulLink
     */
    public function setCategory($category)
    {
        $this->category = $category;
    
        return $this;
    }

    /**
     * Get category
     *
     * @return string 
     */
    public function getCategory()
    {
        return $this->category;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

	public function getLicence()
    {
        return $this->licence;
    }

    public function setLicence(Licence $licence)
    {
        $this->licence = $licence;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function setWebsite($website)
    {
        $this->website = $website;
    }

    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set illustration
     *
     * @param string $illustration
     */
    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    /**
     * Get illustration
     *
     * @return string 
     */
    public function getIllustration()
    {
        return $this->illustration;
    }
}