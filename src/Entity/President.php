<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ausi\SlugGenerator\SlugGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'president')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\PresidentRepository')]
class President
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

	#[ORM\Column(name: 'writingDate', type: 'datetime')]
    protected $writingDate;

	#[ORM\Column(name: 'publicationDate', type: 'datetime')]
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

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $photo;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $logo;

	#[ORM\Column(name: 'numberOfDays', type: "integer", length: 100, options: ["default" => 1])]
	private $numberOfDays;

	public function getRealClass()
	{
		$classname = get_class($this);

		if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
			$classname = $matches[1];
		}

		return $classname;
	}

	public function __construct()
	{
		$this->writingDate = new \DateTime();
		$this->publicationDate = new \DateTime();
	}

	public function getShowRoute()
	{
		return "President_Archive_Read";
	}

	public function getEntityName()
	{
		return get_called_class();
	}

    public function getId()
    {
        return $this->id;
    }

    public function setNumberOfDays($numberOfDays)
    {
        $this->numberOfDays = $numberOfDays;
    }

    public function getNumberOfDays()
    {
        return $this->numberOfDays;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
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
		return "extended/photo/page/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
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

    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    public function getLogo()
    {
        return $this->logo;
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadLogo() {
        if (null === $this->logo) {
            return;
        }

		if(is_object($this->logo))
		{
			$NameFile = basename($this->logo->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->logo->move($this->getTmpUploadRootDir().$this->folder.'/', $NewNameFile);
			}else{
				if (is_object($this->logo))
					$this->logo->move($this->getUploadRootDir().$this->folder.'/', $NewNameFile);
			}
			if (is_object($this->logo))
				$this->setImage($NewNameFile);
		} elseif(filter_var($this->logo, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->logo);
			$pi = pathinfo($this->logo);
			$extension = $res = pathinfo(parse_url($this->logo, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setLogo($filename);
		}
    }
}