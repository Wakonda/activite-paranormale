<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\EventMessage
 *
 * @ORM\Table(name="eventmessage")
 * @ORM\HasLifecycleCallbacks
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type_event", type="string")
 * @ORM\DiscriminatorMap({"eventMessage" = "EventMessage", "music_event_message" = "MusicEventMessage"})
 * @ORM\Entity(repositoryClass="App\Repository\EventMessageRepository")
 */
class EventMessage extends MappedSuperclassBase
{
	const BIRTH_DATE_TYPE = "birthDate";
	const DEATH_DATE_TYPE = "deathDate";
	const EVENT_TYPE = "event";
	const CELEBRATION_TYPE = "celebration";
	const CONVENTION_TYPE = "convention";
	const SAINT_TYPE = "saint";
	const HOROSCOPE_TYPE = "horoscope";
	const FESTIVAL_TYPE = "festival";
    
	/**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	protected $abstractText;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $thumbnail;

    /**
	 * @deprecated
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    protected $dateFrom;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $dayFrom;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $monthFrom;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $yearFrom;

    /**
	 * @deprecated
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    protected $dateTo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $dayTo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $monthTo;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $yearTo;

	// Longitude
    /**
     * @var string $longitude
     *
     * @ORM\Column(type="string", nullable=true)
     */
	private $longitude;

	// Latitude
    /**
     * @var string $latitude
     *
     * @ORM\Column(type="string", nullable=true)
     */
	 private $latitude;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

	/**
	 * @ORM\Column(type="string", length=100)
	 */
	protected $type;

	/**
	 * @var string $wikidata
	 *
	 * @ORM\Column(name="wikidata", type="string", length=15, nullable=true)
	 */
	private $wikidata;

	/**
	 * @var string $internationalName
	 *
	 * @ORM\Column(name="internationalName", type="string", length=255)
	 */
	private $internationalName;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function getDateFromString(): String {
		$yearFrom = $this->yearFrom < 0 ? "-".str_pad(abs($this->yearFrom), 4, "0", STR_PAD_LEFT) : $this->yearFrom;
		$monthFrom = !empty($this->monthFrom) ? str_pad($this->monthFrom, 2, "0", STR_PAD_LEFT) : $this->monthFrom;
		$dayFrom = !empty($this->dayFrom) ? str_pad($this->dayFrom, 2, "0", STR_PAD_LEFT) : $this->dayFrom;

		return implode("-", array_filter([$yearFrom, $monthFrom, $dayFrom]));
	}
	
	public function getDateToString(): String {
		$yearTo = $this->yearTo < 0 ? "-".str_pad(abs($this->yearTo), 4, "0", STR_PAD_LEFT) : $this->yearTo;
		$monthTo = !empty($this->monthTo) ? str_pad($this->monthTo, 2, "0", STR_PAD_LEFT) : $this->monthTo;
		$dayTo = !empty($this->dayTo) ? str_pad($this->dayTo, 2, "0", STR_PAD_LEFT) : $this->dayTo;

		return implode("-", array_filter([$yearTo, $monthTo, $dayTo]));
	}

	public function isDatesEqual() {
		return $this->getDateFromString() == $this->getDateToString();
	}

	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

	public function getShowRoute()
	{
		return "EventMessage_Read";
	}

	public function getWaitingRoute()
	{
		return "EventMessage_Waiting";
	}

	public function withoutYearEvent(): bool {
		return (empty($this->yearFrom) and empty($this->yearTo));
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

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/eventMessage/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadFilePhoto() {
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
	
	/**
     * Set thumbnail
     *
     * @param string $thumbnail
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * Get thumbnail
     *
     * @return string 
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadFileThumbnail() {
        // the file property can be empty if the field is not required
        if (null === $this->thumbnail) {
            return;
        }

		if(is_object($this->thumbnail))
		{
			$NameFile = basename($this->thumbnail->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->thumbnail->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->thumbnail))
					$this->thumbnail->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->thumbnail))
				$this->setThumbnail($NewNameFile);
		} elseif(filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->thumbnail);
			$pi = pathinfo($this->thumbnail);
			$extension = $res = pathinfo(parse_url($this->thumbnail, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setThumbnail($filename);
		}
    }
    /**
     * Set dateFrom
     *
     * @param datetime $dateFrom
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;
    }


    /**
     * Get dateFrom
     *
     * @return date
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * Set dateTo
     *
     * @param datetime $dateTo
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;
    }

    /**
     * Get dateTo
     *
     * @return date
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * Set longitude
     *
     * @param decimal $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * Get longitude
     *
     * @return longitude
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set latitude
     *
     * @param decimal $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * Get latitude
     *
     * @return latitude
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setAbstractText($abstractText)
    {
        $this->abstractText = $abstractText;
    }

    public function getAbstractText()
    {
        return $this->abstractText;
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

    public function setDayFrom($dayFrom)
    {
        $this->dayFrom = $dayFrom;
    }

    public function getDayFrom()
    {
        return $this->dayFrom;
    }

    public function setMonthFrom($monthFrom)
    {
        $this->monthFrom = $monthFrom;
    }

    public function getMonthFrom()
    {
        return $this->monthFrom;
    }

    public function setYearFrom($yearFrom)
    {
        $this->yearFrom = $yearFrom;
    }

    public function getYearFrom()
    {
        return $this->yearFrom;
    }

    public function setDayTo($dayTo)
    {
        $this->dayTo = $dayTo;
    }

    public function getDayTo()
    {
        return $this->dayTo;
    }

    public function setMonthTo($monthTo)
    {
        $this->monthTo = $monthTo;
    }

    public function getMonthTo()
    {
        return $this->monthTo;
    }

    public function setYearTo($yearTo)
    {
        $this->yearTo = $yearTo;
    }

    public function getYearTo()
    {
        return $this->yearTo;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * Set wikidata
     *
     * @param String $wikidata
     */
    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    /**
     * Get wikidata
     *
     * @return String
     */
    public function getWikidata()
    {
        return $this->wikidata;
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
     * @return internationalName 
     */
    public function getInternationalName()
    {
        return $this->internationalName;
    }
}