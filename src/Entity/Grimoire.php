<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Ausi\SlugGenerator\SlugGenerator;

#[ORM\Table(name: 'grimoire')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\GrimoireRepository')]
class Grimoire
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
	#[Assert\NotBlank(message: 'admin.error.NotBlank', groups: ['grimoire_validation'])]
    private $title;

	#[ORM\Column(name: 'text', type: 'text')]
	#[Assert\NotBlank(message: 'admin.error.NotBlank', groups: ['grimoire_validation'])]
    private $text;

	#[ORM\Column(name: 'writingDate', type: 'datetime')]
    private $writingDate;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
	#[Assert\NotBlank(message: 'admin.error.NotBlank', groups: ['grimoire_validation'])]
    private $source;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(name: 'photo', type: 'string', length: 255, nullable: true)]
    private $photo;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
	#[Assert\NotBlank(message: 'admin.error.NotBlank', groups: ['grimoire_validation'])]
    private $language;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\SurThemeGrimoire')]
	#[ORM\JoinColumn(name: 'surTheme_id')]
	#[Assert\NotBlank(message: 'admin.error.NotBlank', groups: ['grimoire_validation'])]
    private $surTheme;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\State')]
    protected $state;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
	#[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true)]
    protected $author;

	#[ORM\Column(name: 'isAnonymous', type: 'string', length: 1, nullable: true)]
    protected $isAnonymous;

	#[ORM\Column(name: 'pseudoUsed', type: 'string', length: 255, nullable: true)]
    protected $pseudoUsed;

	#[ORM\Column(name: 'archive', type: 'boolean', nullable: true)]
    private $archive;

	#[ORM\Column(name: 'publicationDate', type: 'datetime', nullable: true, options: ["default" => null])]
    protected $publicationDate;

	#[ORM\Column(name: 'socialNetworkIdentifiers', type: 'json', nullable: true)]
    private $socialNetworkIdentifiers;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;

	public function __construct()
	{
		$this->writingDate = new \DateTime();
		$this->publicationDate = new \DateTime();
		$this->archive = false;
	}

	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

	public function getUrlSlug() {
		return $this->slug;
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

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
		$this->setSlug();
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

    public function setWritingDate($writingDate)
    {
        $this->writingDate = $writingDate;
    }

    public function getWritingDate()
    {
        return $this->writingDate;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

	public function getFullPicturePath() {
        return null === $this->photo ? null : $this->getUploadRootDir(). $this->photo;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/witchcraft/grimoire/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
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

    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setIsAnonymous($isAnonymous)
    {
        $this->isAnonymous = $isAnonymous;
    }

    public function getIsAnonymous()
    {
        return $this->isAnonymous;
    }

    public function setPseudoUsed($pseudoUsed)
    {
        $this->pseudoUsed = $pseudoUsed;
    }

    public function getPseudoUsed()
    {
        return $this->pseudoUsed;
    }

    public function setArchive($archive)
    {
        $this->archive = $archive;
    }

    public function getArchive()
    {
        return $this->archive;
    }

    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
    }

    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function setSocialNetworkIdentifiers($socialNetworkIdentifiers)
    {
        $this->socialNetworkIdentifiers = $socialNetworkIdentifiers;
    }

    public function getSocialNetworkIdentifiers()
    {
        return $this->socialNetworkIdentifiers;
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