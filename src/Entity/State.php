<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'state')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\StateRepository')]
class State
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\Column(name: 'text', type: 'text')]
    private $text;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
    private $internationalName;

	#[ORM\Column(name: 'displayState', type: 'boolean')]
    private $displayState;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;
	
	public static $draft = "Draft";
	public static $duplicateValues = "duplicateValues";
	public static $preview = "Preview";
	public static $refused = "Refused";
	public static $validate = "Validate";
	public static $waiting = "Waiting";
	public static $warned = "Warned";
	public static $writing = "Writing";

	public function isValidate()
	{
		return $this->internationalName == self::$validate;
	}

	public function isWaiting()
	{
		return $this->internationalName == self::$waiting;
	}

	public function isRefused()
	{
		return $this->internationalName == self::$refused;
	}

	public function isWarned()
	{
		return $this->internationalName == self::$warned;
	}

	public function isPreview()
	{
		return $this->internationalName == self::$preview;
	}

	public function isDraft()
	{
		return $this->internationalName == self::$draft;
	}

	public function isWriting()
	{
		return $this->internationalName == self::$writing;
	}

	public function isDuplicateValues()
	{
		return $this->internationalName == self::$duplicateValues;
	}
	
	public function isStateDisplayed()
	{
		return $this->isValidate() || $this->isWarned();
	}

	public function getPropertyEntityForm()
	{
		return $this->title.' ('.$this->language->getAbbreviation().')';
	}

	public function __toString()
	{
		return $this->title;
	}

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function setDisplayState($displayState)
    {
        $this->displayState = $displayState;
    }

    public function getDisplayState()
    {
        return $this->displayState;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }
}