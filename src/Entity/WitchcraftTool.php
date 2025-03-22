<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Ausi\SlugGenerator\SlugGenerator;

#[ORM\Table(name: 'witchcrafttool')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\WitchcraftToolRepository')]
class WitchcraftTool
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'string', length: 255)]
	protected $title;

	#[ORM\Column(type: 'text', nullable: true)]
	protected $text;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\State')]
    protected $state;
	
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Licence')]
    protected $licence;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    protected $language;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $photo;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(type: 'datetime')]
    protected $writingDate;

	#[ORM\Column(type: 'datetime')]
    protected $publicationDate;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
	#[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true)]
    protected $author;

	#[ORM\Column(name: 'isAnonymous', type: 'string', length: 1, nullable: true)]
    protected $isAnonymous;

	#[ORM\Column(name: 'pseudoUsed', type: 'string', length: 255, nullable: true)]
    protected $pseudoUsed;
	
	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;

	#[ORM\OneToOne(targetEntity: History::class, cascade: ['remove'])]
	#[ORM\JoinColumn(name: 'history_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected $history;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\WitchcraftThemeTool')]
    protected $witchcraftThemeTool;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	public function __construct()
	{
		$this->writingDate = new \DateTime();
		$this->publicationDate = new \DateTime();
	}

	public function __clone()
	{
		if(!empty($this->illustration))
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
	
	public function __toString(): string
	{
		return $this->title;
	}

	public function getShowRoute()
	{
		return "WitchcraftTool_Show";
	}

    public function getId()
    {
        return $this->id;
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
        return null === $this->photo ? null : realpath($this->getUploadRootDir(). $this->photo);
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/witchcrafttools/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadPhoto() {
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

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function getWitchcraftThemeTool()
    {
        return $this->witchcraftThemeTool;
    }

    public function setWitchcraftThemeTool(WitchcraftThemeTool $witchcraftThemeTool)
    {
        $this->witchcraftThemeTool = $witchcraftThemeTool;
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

    public function setSlug()
    {
		if(empty($this->slug)) {
			$generator = new SlugGenerator;
			$this->slug = $generator->generate($this->title);
		}
    }

    public function getSlug()
    {
        return $this->slug;
    }
	
	public function getUrlSlug()
	{
		return !empty($this->slug) ? $this->slug : $this->title;
	}

    public function setText($text)
    {
		$this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

	public function getState()
    {
        return $this->state;
    }

    public function setState(State $state)
    {
        $this->state = $state;
    }

	public function getLicence()
    {
        return $this->licence;
    }

    public function setLicence(Licence $licence)
    {
        $this->licence = $licence;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getTheme()
    {
        return $this->witchcraftThemeTool;
    }

    public function setWritingDate($writingDate)
    {
        $this->writingDate = $writingDate;
    }

    public function getWritingDate()
    {
        return $this->writingDate;
    }

    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
    }

    public function getPublicationDate()
    {
        return $this->publicationDate;
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

    public function setHistory($history)
    {
        $this->history = $history;
    }

    public function getHistory()
    {
        return $this->history;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }
}