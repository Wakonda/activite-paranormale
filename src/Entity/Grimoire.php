<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Grimoire
 *
 * @ORM\Table(name="grimoire")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\GrimoireRepository")
 */
class Grimoire
{
	use \App\Entity\GenericEntityTrait;
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
	 * @Assert\NotBlank(message="admin.error.NotBlank", groups={"grimoire_validation"})
     */
    private $title;

    /**
     * @var string $text
     *
     * @ORM\Column(name="text", type="text")
	 * @Assert\NotBlank(message="admin.error.NotBlank", groups={"grimoire_validation"})
     */
    private $text;

    /**
     * @var string $writingDate
     *
     * @ORM\Column(type="datetime")
     */
    private $writingDate;

    /**
     * @var string $source
     *
     * @ORM\Column(name="source", type="text", nullable=true)
	 * @Assert\NotBlank(message="admin.error.NotBlank", groups={"grimoire_validation"})
     */
    private $source;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(name="photo", type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $illustration;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
	 * @Assert\NotBlank(message="admin.error.NotBlank", groups={"grimoire_validation"})
     */
    private $language;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\SurThemeGrimoire")
	 * @Assert\NotBlank(message="admin.error.NotBlank", groups={"grimoire_validation"})
     */
    private $surTheme;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\State")
     */
    protected $state;

    /**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User")
	 * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=true)
     */
    protected $author;

    /**
     * @ORM\Column(name="isAnonymous", type="string", length=1, nullable=true)
     */
    protected $isAnonymous;
	
	/**
     * @ORM\Column(name="pseudoUsed", type="string", length=255, nullable=true)
     */
    protected $pseudoUsed;

    /**
     * @ORM\Column(name="archive", type="boolean", nullable=true)
     */
    private $archive;

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default" : null})
     */
    protected $publicationDate;

    /**
     * @ORM\Column(name="socialNetworkIdentifiers", type="json", nullable=true)
     */
    private $socialNetworkIdentifiers;

	public function __construct()
	{
		$this->writingDate = new \DateTime();
		$this->publicationDate = new \DateTime();
		$this->archive = false;
	}
	
	public function __clone()
	{
		$this->illustration = clone $this->illustration;
	}

    public function getPhotoIllustrationCaption(): ?Array
    {
		if(method_exists($this, "getIllustration") and !empty($this->getIllustration()))
			return [
				"caption" => $this->getIllustration()->getCaption(),
				"source" => ["author" => $this->getIllustration()->getAuthor(), "license" => $this->getIllustration()->getLicense(), "url" => $this->getIllustration()->getUrlSource()]
		    ];
		
		return [];
    }

	public function getPdfVersionRoute()
	{
		return "Witchcraft_Pdfversion";
	}

	public function getShowRoute()
	{
		return "Witchcraft_ReadGrimoire_Simple";
	}

	public function getWaitingRoute()
	{
		return "Witchcraft_Waiting";
	}

	public function authorToString()
	{
		if($this->isAnonymous == 1)
		{
			if($this->pseudoUsed != "")
				return $this->pseudoUsed;

			return "Anonymous";
		}
		else
		{
			if($this->author == null)
				return $this->pseudoUsed;
			else
				return $this->author->getUsername();
		}
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
     * Set writingDate
     *
     * @param string $writingDate
     */
    public function setWritingDate($writingDate)
    {
        $this->writingDate = $writingDate;
    }

    /**
     * Get writingDate
     *
     * @return string 
     */
    public function getWritingDate()
    {
        return $this->writingDate;
    }

    /**
     * Set source
     *
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Get source
     *
     * @return string 
     */
    public function getSource()
    {
        return $this->source;
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

	public function getAssetImagePath()
	{
		return "extended/photo/witchcraft/grimoire/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadPhotoGrimoire() {
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
			$filename = $pi["filename"].".".$pi["extension"];
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setPhoto($filename);
		}
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getSurTheme()
    {
        return $this->surTheme;
    }

    public function setSurTheme(SurThemeGrimoire $surTheme)
    {
        $this->surTheme = $surTheme;
    }

	public function getState()
    {
        return $this->state;
    }

    public function setState(State $state)
    {
        $this->state = $state;
    }

    /**
     * Set author
     *
     * @param App\Entity\User $author
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    /**
     * Get author
     *
     * @return App\Entity\User 
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set isAnonymous
     *
     * @param string $isAnonymous
     */
    public function setIsAnonymous($isAnonymous)
    {
        $this->isAnonymous = $isAnonymous;
    }

    /**
     * Get isAnonymous
     *
     * @return string 
     */
    public function getIsAnonymous()
    {
        return $this->isAnonymous;
    }

    /**
     * Set pseudoUsed
     *
     * @param string $pseudoUsed
     */
    public function setPseudoUsed($pseudoUsed)
    {
        $this->pseudoUsed = $pseudoUsed;
    }

    /**
     * Get pseudoUsed
     *
     * @return string 
     */
    public function getPseudoUsed()
    {
        return $this->pseudoUsed;
    }

    /**
     * Set archive
     *
     * @param boolean $archive
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;
    }

    /**
     * Get archive
     *
     * @return boolean 
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Set publicationDate
     *
     * @param date $publicationDate
     */
    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
    }

    /**
     * Get publicationDate
     *
     * @return date
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
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
}