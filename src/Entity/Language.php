<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'language')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\LanguageRepository')]
class Language
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
	#[Groups('api_read')]
    private $title;

	#[ORM\Column(name: 'abbreviation', type: 'string', length: 255)]
	#[Groups('api_read')]
    private $abbreviation;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(nullable: true, type: 'string', length: 255)]
    private $logo;

	#[ORM\Column(name: 'direction', type: 'string', length: 3)]
	private $direction;

	#[ORM\Column(name: 'current', type: 'boolean')]
	private $current;

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

    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
    }

    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    public function getLogo()
    {
        return $this->logo;
    }

	public function setDirection($direction)
	{
		$this->direction = $direction;
	}

	public function getDirection()
	{
		return $this->direction;
	}
	
	public function getFullPicturePath() {
        return null === $this->logo ? null : $this->getUploadRootDir(). $this->logo;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/language/";
	}
	
    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadIconeLangue() {
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
				$this->logo->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->logo))
					$this->logo->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->logo))
				$this->setLogo($NewNameFile);
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

	public function setCurrent($current)
	{
		$this->current = $current;
	}

	public function getCurrent()
	{
		return $this->current;
	}
}