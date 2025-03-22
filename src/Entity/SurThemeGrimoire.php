<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Service\APPurifierHTML;

#[ORM\Table(name: 'surthemegrimoire')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\SurThemeGrimoireRepository')]
class SurThemeGrimoire
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
	#[Assert\NotBlank(groups: ['stg_validation'])]
    private $title;

	#[ORM\Column(name: 'theme', type: 'string', length: 255, nullable: true)]
	#[Assert\NotBlank(groups: ['stg_validation'])]
    private $theme;

	#[ORM\Column(name: 'text', type: 'text', nullable: true)]
	#[Assert\NotBlank(groups: ['stg_validation'])]
    private $text;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
	#[Assert\NotBlank(groups: ['stg_validation'])]
    private $photo;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\MenuGrimoire')]
	#[ORM\JoinColumn(name: 'menuGrimoire_id')]
	#[Assert\NotBlank(groups: ['stg_validation'])]
    private $menuGrimoire;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\SurThemeGrimoire')]
	#[ORM\JoinColumn(name: 'parentTheme_id')]
	#[Assert\NotBlank(groups: ['stg_validation'])]
    private $parentTheme;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
	#[Assert\NotBlank(groups: ['stg_validation'])]
    private $language;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	#[Assert\NotBlank(groups: ['stg_validation'])]
    private $internationalName;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;
	
	public function __toString()
	{
		return $this->title;
	}

	public function getUrlSlug() {
		return $this->slug;
	}

	public function getMenuGrimoireTitle()
	{
		return !empty($parentTheme = $this->parentTheme) ? $parentTheme->getTitle() : null;
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

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function setText($text)
    {
		$purifier = new APPurifierHTML();
		$this->text = $purifier->purifier($text);
    }

    public function getText()
    {
        return $this->text;
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
		return "extended/photo/witchcraft/surTheme/";
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

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getMenuGrimoire()
    {
        return $this->menuGrimoire;
    }

    public function setMenuGrimoire(MenuGrimoire $menuGrimoire)
    {
        $this->menuGrimoire = $menuGrimoire;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function getParentTheme()
    {
        return $this->parentTheme;
    }

    public function setParentTheme(SurThemeGrimoire $parentTheme)
    {
        $this->parentTheme = $parentTheme;
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