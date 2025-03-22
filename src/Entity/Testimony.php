<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'testimony')]
#[ORM\Entity(repositoryClass: 'App\Repository\TestimonyRepository')]
class Testimony extends MappedSuperclassBase
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'emailAuthor', type: 'string', length: 255, nullable: true)]
    private $emailAuthor;

	#[ORM\Column(name: 'location', type: 'text', nullable: true)]
    private $location;

	#[ORM\Column(name: 'sightingDate', type: 'string', length: 18, nullable: true)]
    private $sightingDate;
	
	public function __construct()
	{
		parent::__construct();
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

	public function getPdfVersionRoute()
	{
		return "Testimony_Pdfversion";
	}

	public function getShowRoute()
	{
		return "Testimony_Show";
	}

	public function getWaitingRoute()
	{
		return "Testimony_Waiting";
	}

	public function getAssetImagePath()
	{
		return "extended/photo/testimony/";
	}

    public function getId()
    {
        return $this->id;
    }

    public function setEmailAuthor($emailAuthor)
    {
        $this->emailAuthor = $emailAuthor;
    }

    public function getEmailAuthor()
    {
        return $this->emailAuthor;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setSightingDate($sightingDate)
    {
        $this->sightingDate = $sightingDate;
    }

    public function getSightingDate()
    {
        return $this->sightingDate;
    }
}