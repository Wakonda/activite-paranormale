<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'licence')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\LicenceRepository')]
class Licence
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\Column(name: 'text', type: 'text')]
    private $text;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(name: 'logo', type: 'string', length: 255)]
    private $logo;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
    private $internationalName;

	#[ORM\Column(name: 'link', type: 'string', length: 255)]
	#[Assert\NotBlank(message: 'admin.error.NotBlank', groups: ['licence_validation'])]
    private $link;

	public function __toString()
	{
		return $this->title;
	}

	public function getPropertyEntityForm()
	{
		return $this->title.' ('.$this->language->getAbbreviation().')';
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

    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    public function getLogo()
    {
        return $this->logo;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/licence/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
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

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function getLink()
    {
        return $this->link;
    }
}