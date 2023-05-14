<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * App\Entity\Quotation
 *
 * @ORM\Table(name="quotation")
 * @ORM\Entity(repositoryClass="App\Repository\QuotationRepository")
 */
class Quotation
{
	const QUOTATION_FAMILY = "quotation";
	const PROVERB_FAMILY = "proverb";

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Biography")
     */
    private $authorQuotation;

    /**
     * @var text $textQuotation
     *
     * @ORM\Column(name="textQuotation", type="text")
     */
    private $textQuotation;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    private $language;

	/**
	 * @ORM\Column(name="source", type="text", nullable=true)
	 *
	 */
	private $source;
	
	/**
	 * @ORM\Column(name="explanation", type="text", nullable=true)
	 *
	 */
	private $explanation;

    /**
     * @ORM\OneToMany(targetEntity=QuotationImage::class, cascade={"persist", "remove"}, mappedBy="quotation", orphanRemoval=true)
     */
    protected $images;

    /**
     * @var string $tags
     *
     * @ORM\Column(name="tags", type="json", nullable=true)
     */
    private $tags;

    /**
     * @ORM\Column(name="identifier", type="text", nullable=true)
     */
    private $identifier;

	/**
	 * @ORM\Column(type="string", nullable=true) 
	 */
	protected $family;
	
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Country")
     */
    protected $country;

	public function __construct()
	{
		$this->images = new ArrayCollection();
	}
	
	public function isQuotationFamily(): boolean {
		return $this->family == self::QUOTATION_FAMILY;
	}
	
	public function isProverbFamily(): boolean {
		return $this->family == self::PROVERB_FAMILY;
	}

	public function getTitle() {
		return $this->textQuotation;
	}

	public function getShowRoute()
	{
		return "Quotation_ReadQuotation";
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

    public function setAuthorQuotation($authorQuotation)
    {
        $this->authorQuotation = $authorQuotation;
    }

    public function getAuthorQuotation()
    {
        return $this->authorQuotation;
    }

    /**
     * Set textQuotation
     *
     * @param text $textQuotation
     */
    public function setTextQuotation($textQuotation)
    {
        $this->textQuotation = $textQuotation;
    }

    /**
     * Get textQuotation
     *
     * @return text 
     */
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
}