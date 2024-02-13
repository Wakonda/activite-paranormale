<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\State
 *
 * @ORM\Table(name="state")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\StateRepository")
 */
class State
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var text $text
     *
     * @ORM\Column(name="text", type="text")
     */
    private $text;

    /**
     * @var string $internationalName
     *
     * @ORM\Column(name="internationalName", type="string", length=255)
     */
    private $internationalName;
	
    /**
     * @ORM\Column(name="displayState", type="boolean")
     */
    private $displayState;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
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
     * @param text $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Get text
     *
     * @return text 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Get internationalName
     *
     * @return string 
     */
    public function getInternationalName()
    {
        return $this->internationalName;
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
     * Set displayState
     *
     * @param string $displayState
     */
    public function setDisplayState($displayState)
    {
        $this->displayState = $displayState;
    }

    /**
     * Get displayState
     *
     * @return string 
     */
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