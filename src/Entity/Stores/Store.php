<?php

namespace App\Entity\Stores;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use App\Filter\OrSearchFilter;

use App\Entity\Language;

/**
 * App\Entity\Store
 *
 * @ORM\Entity(repositoryClass="App\Repository\StoreRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"store" = "Store", "book_store" = "BookStore", "album_store" = "AlbumStore", "movie_store" = "MovieStore", "televisionserie_store" = "TelevisionSerieStore", "witchcrafttool_store" = "WitchcraftToolStore"})
 * @ApiResource(normalizationContext = {"groups" = {"api_read"}}, collectionOperations = {"GET"}, itemOperations = {"GET"})
 * @ApiFilter(SearchFilter::class, properties = {"category" = "exact"})
 * @ApiFilter(OrderFilter::class, properties = {"id"}, arguments = {"orderParameterName" = "order"})
 * @ApiFilter(OrSearchFilter::class, properties={"title", "text"})
 */

class Store
{
	use \App\Entity\GenericEntityTrait;
	
	const ALIEXPRESS_PLATFORM = "aliexpress";
	const AMAZON_PLATFORM = "amazon";
	
	const BOOK_CATEGORY = "book";
	const CLOTH_CATEGORY = "cloth";
	const FUNNY_CATEGORY = "funny";
	const ALBUM_CATEGORY = "album";
	const MOVIE_CATEGORY = "movie";
	const TELEVISION_SERIE_CATEGORY = "televisionSerie";
	const WITCHCRAFT_TOOL_CATEGORY = "witchcraftTool";
	
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
	 * @Groups("api_read")
     */
    private $title;

    /**
     * @ORM\Column(type="text", nullable=true)
	 * @Groups("api_read")
     */
    private $text;

    /**
     * @ORM\Column(type="text", nullable=true)
	 * @Groups("api_read")
     */
    private $url;

    /**
     * @ORM\Column(type="text")
	 * @Groups("api_read")
     */
    private $imageEmbeddedCode;

    /**
     * @var float $price
     *
     * @ORM\Column(type="float", nullable=true)
	 * @Groups("api_read")
     */
    private $price;

    /**
     * @var string $currencyPrice
     *
     * @ORM\Column(type="string", length=10, nullable=true)
	 * @Groups("api_read")
     */
    private $currencyPrice;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
	 * @Groups("api_read")
     */
    private $amazonCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
	 * @Groups("api_read")
     */
    private $platform;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
	 * @Groups("api_read")
     */
    private $category;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
	 * @Groups("api_read")
     */
    protected $language;

    /**
     * @ORM\Column(type="text", nullable=true)
	 * @Groups("api_read")
     */
    private $characteristic;
	
	public function getLinkedEntityName() {
		return null;
	}
	
	public function isClothCategory(): bool {
		return $this->category == self::CLOTH_CATEGORY;
	}
	
	public function isBookCategory(): bool {
		return $this->category == self::BOOK_CATEGORY;
	}
	
	public function isAlbumCategory(): bool {
		return $this->category == self::ALBUM_CATEGORY;
	}
	
	public function isTelevisionSerieCategory(): bool {
		return $this->category == self::TELEVISION_SERIE_CATEGORY;
	}
	
	public function isMovieCategory(): bool {
		return $this->category == self::MOVIE_CATEGORY;
	}
	
	public function isWitchcraftToolCategory(): bool {
		return $this->category == self::WITCHCRAFT_TOOL_CATEGORY;
	}

	const partnerId = "activiparano-21";
	
    /**
	 * @Groups("api_read")
     */
	public function getExternalAmazonStoreLink()
	{
		return "http://www.amazon.fr/dp/".$this->amazonCode."/ref=nosim?tag=".self::partnerId;
	}

	public function getAssetImagePath()
	{
		return "extended/photo/store/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
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
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     */
    public function setText($text)
    {
        $this->text = $text;
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
     * Set imageEmbeddedCode
     *
     * @param string $imageEmbeddedCode
     */
    public function setImageEmbeddedCode($imageEmbeddedCode)
    {
        $this->imageEmbeddedCode = $imageEmbeddedCode;
    }

    /**
     * Get imageEmbeddedCode
     *
     * @return string
     */
    public function getImageEmbeddedCode()
    {
        return $this->imageEmbeddedCode;
    }

    /**
     * Set price
     *
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * Get price
     *
     * @return float 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set currencyPrice
     *
     * @param string $currencyPrice
     */
    public function setCurrencyPrice($currencyPrice)
    {
        $this->currencyPrice = $currencyPrice;
    }

    /**
     * Get currencyPrice
     *
     * @return string 
     */
    public function getCurrencyPrice()
    {
        return $this->currencyPrice;
    }

    /**
     * Set category
     *
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
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

    /**
     * Set amazonCode
     *
     * @param integer $amazonCode
     */
    public function setAmazonCode($amazonCode)
    {
        $this->amazonCode = $amazonCode;
    }

    /**
     * Get amazonCode
     *
     * @return integer 
     */
    public function getAmazonCode()
    {
        return $this->amazonCode;
    }

    /**
     * Set platform
     *
     * @param string $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * Get platform
     *
     * @return string 
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getCharacteristic()
    {
        return json_decode($this->characteristic, true);
    }

    public function setCharacteristic($characteristic)
    {
        $this->characteristic = json_encode($characteristic);
    }
}