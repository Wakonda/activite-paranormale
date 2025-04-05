<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Table(name: 'quotation')]
#[ORM\Entity(repositoryClass: 'App\Repository\QuotationRepository')]
class Quotation
{
	const QUOTATION_FAMILY = "quotation";
	const PROVERB_FAMILY = "proverb";
	const POEM_FAMILY = "poem";
	const HUMOR_FAMILY = "humor";
	const SAYING_FAMILY = "saying";
	const LYRIC_FAMILY = "lyric";

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Biography')]
	#[ORM\JoinColumn(name: 'authorQuotation_id')]
    private $authorQuotation;

	#[ORM\Column(name: 'title', type: 'string', nullable: true)]
    private $title;

	#[ORM\Column(name: 'textQuotation', type: 'text')]
    private $textQuotation;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
	private $source;

	#[ORM\Column(name: 'explanation', type: 'text', nullable: true)]
	private $explanation;

	#[ORM\OneToMany(targetEntity: QuotationImage::class, cascade: ['persist', 'remove'], mappedBy: 'quotation', orphanRemoval: true)]
    protected $images;

	#[ORM\Column(name: 'tags', type: 'text', nullable: true)]
    private $tags;

	#[ORM\Column(name: 'identifier', type: 'text', nullable: true)]
    private $identifier;

	#[ORM\Column(type: 'string', length: 255)]
	protected $family;

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
	protected $date;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Region')]
    protected $country;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Music')]
    protected $music;

	public function __construct()
	{
		$this->images = new ArrayCollection();
	}
	
	public function isQuotationFamily(): bool {
		return $this->family == self::QUOTATION_FAMILY;
	}
	
	public function isProverbFamily(): bool {
		return $this->family == self::PROVERB_FAMILY;
	}
	
	public function isPoemFamily(): bool {
		return $this->family == self::POEM_FAMILY;
	}

	public function isHumorFamily(): bool {
		return $this->family == self::HUMOR_FAMILY;
	}

	public function isSayingFamily(): bool {
		return $this->family == self::SAYING_FAMILY;
	}

	public function isLyricFamily(): bool {
		return $this->family == self::LYRIC_FAMILY;
	}

	public function getTitle() {
		if($this->isPoemFamily())
			return $this->title;

		return $this->textQuotation;
	}
	
	public function getUrlSlug() {
		return $this->getTitle();
	}

	public function getShowRoute()
	{
		if($this->isProverbFamily())
			return "Proverb_Read";
		elseif($this->isPoemFamily())
			return "Poem_Read";
		elseif($this->isHumorFamily())
			return "Humor_Read";

		return "Quotation_Read";
	}

	public function getEntityName()
	{
		return get_called_class();
	}

    public function getId()
    {
        return $this->id;
    }

    public function setAuthorQuotation($authorQuotation)
    {
        $this->authorQuotation = $authorQuotation;
    }

    public function getAuthorQuotation()
    {
        return $this->authorQuotation;
    }

    public function setTextQuotation($textQuotation)
    {
        $this->textQuotation = $textQuotation;
    }

    public function getTextQuotation()
    {
        return html_entity_decode($this->textQuotation, ENT_QUOTES);
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }
	
	public function getExplanation()
	{
		return $this->explanation;
	}
	
	public function setExplanation($explanation)
	{
		$this->explanation = $explanation;
	}

	public function getAssetImagePath()
	{
		return "extended/photo/quotation/";
	}

    public function getImages()
    {
        return $this->images;
    }
     
    public function addImage(QuotationImage $image)
    {
        $this->images->add($image);
        $image->setQuotation($this);
    }
	
    public function removeImage(QuotationImage $image)
    {
        $image->setQuotation(null);
        $this->images->removeElement($image);
    }

    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
	
	public function getTags()
	{
		return $this->tags;
	}
	
	public function setTags($tags)
	{
		$this->tags = $tags;
	}
	
	public function getFamily()
	{
		return $this->family;
	}
	
	public function setFamily($family)
	{
		$this->family = $family;
	}
	
	public function getCountry()
	{
		return $this->country;
	}
	
	public function setCountry($country)
	{
		$this->country = $country;
	}
	
	public function getDate()
	{
		return $this->date;
	}

	public function setDate($date)
	{
		$this->date = $date;
	}

	public function setTitle($title)
	{
		$this->title = $title;
	}

	public function getMusic()
	{
		return $this->music;
	}

	public function setMusic($music)
	{
		$this->music = $music;
	}
}