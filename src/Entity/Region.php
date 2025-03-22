<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'region')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\RegionRepository')]
class Region
{
	const SUBDIVISION_FAMILY = "subdivision";
	const COUNTRY_FAMILY = "country";
	const AREA_FAMILY = "area";

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
    private $internationalName;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(name: 'flag', type: 'string', length: 255, nullable: true)]
    private $flag;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'family', type: 'string', length: 255)]
    private $family;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Region')]
    protected $higherLevel;
	
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

    public function setFlag($flag)
    {
        $this->flag = $flag;
    }

    public function getFlag()
    {
        return $this->flag;
    }
	
	public function getFullPicturePath() {
        return null === $this->flag ? null : $this->getUploadRootDir(). $this->flag;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/country/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadFlagCountry() {
        if (null === $this->flag) {
            return;
        }

		if(is_object($this->flag))
		{
			$NameFile = basename($this->flag->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->flag->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->flag))
					$this->flag->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->flag))
				$this->setFlag($NewNameFile);
		} elseif(filter_var($this->flag, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->flag);
			$pi = pathinfo($this->flag);
			$extension = $res = pathinfo(parse_url($this->flag, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setFlag($filename);
		}
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getFamily()
    {
        return $this->family;
    }

    public function setFamily($family)
    {
        $this->family = $family;
    }

    public function getHigherLevel()
    {
        return $this->higherLevel;
    }

    public function setHigherLevel($higherLevel)
    {
        $this->higherLevel = $higherLevel;
    }
}