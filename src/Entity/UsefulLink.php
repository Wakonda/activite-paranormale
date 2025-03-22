<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use App\Filter\OrSearchFilter;
use Ausi\SlugGenerator\SlugGenerator;

/**
 * App\Entity\UsefulLink
 *
#[ORM\Table(name: 'usefullink")
#[ORM\Entity(repositoryClass: 'App\Repository\UsefulLinkRepository")
 * @ApiResource(normalizationContext = {"groups" = {"api_read"}}, collectionOperations = {"GET"}, itemOperations = {"GET"})
 * @ApiFilter(SearchFilter::class, properties = {"category" = "exact", "usefullinkTags.title" = "exact"})
 * @ApiFilter(OrderFilter::class, properties = {"id"}, arguments = {"orderParameterName" = "order"})
 * @ApiFilter(OrSearchFilter::class, properties={"title", "text"})
 */
class UsefulLink
{
	use \App\Entity\GenericEntityTrait;

	const DEVELOPMENT_FAMILY = "development";
	const RESOURCE_FAMILY = "resource";
	const TOOL_FAMILY = "tool";
	const USEFULLINK_FAMILY = "usefullink";
	const TECHNICAL_FAMILY = "technical";

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ApiProperty(identifier: true)]
	#[Groups('api_read')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
	#[Groups('api_read')]
    private $title;

	#[ORM\Column(name: 'text', type: 'text', nullable: true)]
	#[Groups('api_read')]
    private $text;

	#[ORM\Column(name: 'links', type: 'text', nullable: true)]
	#[Groups('api_read')]
    private $links;

	#[ORM\Column(name: 'tags', type: 'json', nullable: true)]
	#[Groups('api_read')]
    private $tags;

	#[ORM\Column(name: 'category', type: 'string', length: 100, nullable: true)]
	#[Groups('api_read')]
    private $category;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    protected $language;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Licence')]
	#[Groups('api_read')]
    protected $licence;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Blog')]
	#[Groups('api_read')]
	private $website;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
	#[Groups('api_read')]
    private $illustration;

	#[ORM\Column(name: 'datetime', nullable: true)]
	#[Groups('api_read')]
	private $createdAt = null;

	#[ORM\Column(name: 'datetime', nullable: true)]
	#[Groups('api_read')]
	private $updatedAt = null;

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
	#[Groups('api_read')]
	private $slug;

	#[ORM\ManyToMany(targetEntity: 'App\Entity\UsefullinkTags')]
	#[Groups('api_read')]
    private $usefullinkTags;

	#[Groups('api_read')]
	public function getImgPath(): ?array
	{
		if(!empty($this->illustration) and file_exists($f = $this->getAssetImagePath().$this->illustration->getRealNameFile()))
			return ["file" => $f, "author" => $this->illustration->getAuthor(), "license" => $this->illustration->getLicense(), "urlSource" => $this->illustration->getUrlSource()];

		return null;
	}

	public function __construct() {
		$this->createdAt = new \DateTime();
		$this->usefullinkTags = new \Doctrine\Common\Collections\ArrayCollection();
	}
	
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
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	public function getEntityName()
	{
		return get_called_class();
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

    public function setText($text)
    {
        $this->text = $text;
    
        return $this;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setLinks($links)
    {
        $this->links = $links;
    
        return $this;
    }

    public function getLinks()
    {
        return $this->links;
    }

    public function setTags($tags)
    {
        $this->tags = json_decode($tags);
    
        return $this;
    }

    public function getTags()
    {
        return is_null($this->tags) ? [] : json_encode($this->tags);
    }

    public function setCategory($category)
    {
        $this->category = $category;
    
        return $this;
    }

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

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
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

	public function addUsefullinkTag(UsefullinkTags $usefullinkTag)
	{
		$this->usefullinkTags[] = $usefullinkTag;
	}

    public function setUsefullinkTags($usefullinkTags)
    {
        $this->usefullinkTags = $usefullinkTags;
    }

	public function removeTag(UsefullinkTags $usefullinkTag)
	{
		$this->usefullinkTags->removeElement($usefullinkTag);
	}

	public function getUsefullinkTags()
	{
		return $this->usefullinkTags;
	}
}