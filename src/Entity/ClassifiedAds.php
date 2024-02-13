<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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

	public function getAssetImagePath(): String
	{
		return "extended/photo/classifiedads/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
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
}