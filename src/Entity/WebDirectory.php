<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'webdirectory')]
#[ORM\Entity(repositoryClass: 'App\Repository\WebDirectoryRepository')]
class WebDirectory
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255, nullable: true)]
    private $title;

	#[ORM\Column(name: 'link', type: 'string', length: 255, nullable: true)]
    private $link;

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $logo;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
	#[ORM\JoinColumn(name: 'websiteLanguage_id')]
    private $websiteLanguage;

	#[ORM\Column(name: 'socialNetwork', type: 'text', nullable: true)]
    private $socialNetwork;

	#[ORM\Column(type: 'text', nullable: true)]
	protected $text;

	#[ORM\Column(name: 'foundedYear', type: 'string', length: 10, nullable: true)]
	private $foundedYear;

	#[ORM\Column(name: 'defunctYear', type: 'string', length: 10, nullable: true)]
	private $defunctYear;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Licence')]
    protected $licence;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\State')]
    protected $state;

	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
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

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    public function getLogo()
    {
        return $this->logo;
    }

	public function getFullPicturePath() {
        return null === $this->logo ? null : $this->getUploadRootDir(). $this->logo;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/webdirectory/";
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

    public function setSocialNetwork($socialNetwork)
    {
        $this->socialNetwork = $socialNetwork;
    }

    public function getSocialNetwork()
    {
        return $this->socialNetwork;
    }

    public function setText($text)
    {
		$this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setFoundedYear(?string $foundedYear)
    {
		$this->foundedYear = $foundedYear;
    }

    public function getFoundedYear()
    {
        return $this->foundedYear;
    }

    public function setDefunctYear(?string $defunctYear)
    {
		$this->defunctYear = $defunctYear;
    }

    public function getDefunctYear()
    {
        return $this->defunctYear;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

	public function getLicence()
    {
        return $this->licence;
    }

    public function setLicence(?Licence $licence)
    {
        $this->licence = $licence;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }
	
    public function getWebsiteLanguage()
    {
        return $this->websiteLanguage;
    }

    public function setWebsiteLanguage(Language $websiteLanguage)
    {
        $this->websiteLanguage = $websiteLanguage;
    }

	public function getState()
    {
        return $this->state;
    }

    public function setState(State $state)
    {
        $this->state = $state;
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