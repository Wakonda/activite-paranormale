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
use Ausi\SlugGenerator\SlugGenerator;

use App\Entity\Language;

/**
 * App\Entity\Store
 *
 * @ORM\HasLifecycleCallbacks
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
	const SPREADSHOP_PLATFORM = "spreadshop";
	const TEMU_PLATFORM = "temu";
	
	const BOOK_CATEGORY = "book";
	const CLOTH_CATEGORY = "cloth";
	const GOTHIC_CLOTH_CATEGORY = "gothicCloth";
	const FUNNY_CATEGORY = "funny";
	const ALBUM_CATEGORY = "album";
	const MOVIE_CATEGORY = "movie";
	const TELEVISION_SERIE_CATEGORY = "televisionSerie";
	const WITCHCRAFT_TOOL_CATEGORY = "witchcraftTool";
	const STICKER_CATEGORY = "sticker";
	const MUG_CATEGORY = "mug";
	const JEWEL_CATEGORY = "jewel";
	
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
     * @ORM\Column(type="text", nullable=true)
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

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\Column(name="socialNetworkIdentifiers", type="json", nullable=true)
     */
    private $socialNetworkIdentifiers;

	/**
     * @ORM\Column(name="slug", type="string", length=255, nullable=true)
     */
    protected $slug;
	
	public function getShowRoute(): string {
		return "Store_Show";
	}
	
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
	
	public function isStickerCategory(): bool {
		return $this->category == self::STICKER_CATEGORY;
	}
	
	public function isJewelCategory(): bool {
		return $this->category == self::JEWEL_CATEGORY;
	}
	
	public function isMugCategory(): bool {
		return $this->category == self::MUG_CATEGORY;
	}
	
	public function isSpreadShopPlatform(): bool {
		return $this->platform == self::SPREADSHOP_PLATFORM;
	}

	public function getTitleSlug() {
		return $this->slug;
	}

    /**
	 * @Groups("api_read")
     */
	public function getExternalAmazonStoreLink()
	{
		$idPartner = null;
		$urlPartner = $_ENV["AMAZON_FR_URL"];
		
		if(!empty($html = $this->imageEmbeddedCode)) {
			$dom = new \DOMDocument();
			libxml_use_internal_errors(true);
			$dom->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED );

			$xpath = new \DOMXPath($dom);

			if(!empty($xpath->query('//a')->item(0))) {
				$url = $xpath->query('//a')->item(0)->getAttribute("href");

				$urlPart = parse_str(parse_url($url)['query'], $resultUrl);
				
				$idPartner = $resultUrl["tag"];
			}
		}

		$urlPartner = $_ENV["AMAZON_".strtoupper($this->language->getAbbreviation())."_URL"];
		$idPartner = empty($idPartner) ? $_ENV["AMAZON_".strtoupper($this->language->getAbbreviation())."_PARTNER_ID"] : $idPartner;

		return $urlPartner.$this->amazonCode."/ref=nosim?tag=".$idPartner;
	}

	public function getAssetImagePath()
	{
		return "extended/photo/store/";
	}

	public function getUrlSlug() {
		return $this->slug;
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
		if(empty($this->characteristic))
			return null;

        return json_decode($this->characteristic, true);
    }

    public function setCharacteristic($characteristic)
    {
        $this->characteristic = json_encode($characteristic);
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
	
	/**
     * Set photo
     *
     * @param string $photo
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    /**
     * Get photo
     *
     * @return string 
     */
    public function getPhoto()
    {
        return $this->photo;
    }

	public function getFullPicturePath() {
        return null === $this->photo ? null : $this->getUploadRootDir(). $this->photo;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetPhotoPath()
	{
		return "extended/photo/store/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../../public/'.$this->getAssetPhotoPath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadPhoto() {
        // the file property can be empty if the field is not required
        if (null === $this->photo) {
            return;
        }

		if(is_object($this->photo))
		{
			$NameFile = basename($this->photo->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->photo->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->photo))
					$this->photo->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->photo))
				$this->setPhoto($NewNameFile);
		} elseif(filter_var($this->photo, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->photo);
			$pi = pathinfo($this->photo);
			$extension = $res = pathinfo(parse_url($this->photo, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setPhoto($filename);
		}
    }

    public function setSlug()
    {
		if(empty($this->slug))
			$this->slug = (new SlugGenerator)->generate($this->title);
    }

    public function getSlug()
    {
        return $this->slug;
    }
}