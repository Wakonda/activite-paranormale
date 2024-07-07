<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\ClassifiedAds
 *
 * @ORM\Table(name="classified_ads")
 * @ORM\Entity(repositoryClass="App\Repository\ClassifiedAdsRepository")
 */
class ClassifiedAds extends MappedSuperclassBase implements Interfaces\PhotoIllustrationInterface
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="location", type="text", nullable=true)
     */
    private $location;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $currencyPrice;

    /**
     * @ORM\Column(type="boolean")
     */
    private $displayEmail;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\ClassifiedAdsCategory")
     */
    private $category;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $markAs;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $contactName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $contactEmail;

	public function __construct()
	{
		parent::__construct();
	}

	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

	public function getShowRoute()
	{
		return "ClassifiedAds_Read";
	}
	
	public function getWaitingRoute()
	{
		return "ClassifiedAds_Read";
	}

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath(): String
	{
		return "extended/photo/classifiedads/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	public function getLocationArray()
	{
		if(empty($this->location))
			return [];
		
		$res = [];
		$location = json_decode($this->location);
		
		if(property_exists($location, "village"))
			$res["city"] = $location->village;
		
		if(property_exists($location, "town"))
			$res["city"] = $location->town;
		
		if(property_exists($location, "city"))
			$res["city"] = $location->city;
		
		if(property_exists($location, "postcode"))
			$res["postalCode"] = $location->postcode;
		
		if(property_exists($location, "county"))
			$res["county"] = $location->county;
		
		if(property_exists($location, "state"))
			$res["state"] = $location->state;
		
		if(property_exists($location, "country"))
			$res["country"] = $location->country;

		return $res;
	}

    public function getId()
    {
        return $this->id;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setCurrencyPrice($currencyPrice)
    {
        $this->currencyPrice = $currencyPrice;
    }

    public function getCurrencyPrice()
    {
        return $this->currencyPrice;
    }

    public function setDisplayEmail($displayEmail)
    {
        $this->displayEmail = $displayEmail;
    }

    public function getDisplayEmail()
    {
        return $this->displayEmail;
    }

    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function setMarkAs($markAs)
    {
        $this->markAs = $markAs;
    }

    public function getMarkAs()
    {
        return $this->markAs;
    }

    public function setContactName($contactName)
    {
        $this->contactName = $contactName;
    }

    public function getContactName()
    {
        return $this->contactName;
    }

    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    public function getContactEmail()
    {
        return $this->contactEmail;
    }
}