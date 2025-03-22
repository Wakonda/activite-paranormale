<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'banner')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\BannerRepository')]
class Banner
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\Column(name: 'link', type: 'string', length: 255)]
	#[Assert\Url]
    private $link;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $image;

	#[ORM\Column(name: 'display', type: 'boolean')]
    private $display;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setLink($link)
    {
        $this->link = $link;
    
        return $this;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setImage($image)
    {
        $this->image = $image;
    
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

	public function getFullPicturePath()
	{
        return null === $this->image ? null : $this->getUploadRootDir(). $this->image;
    }

    public function getUploadRootDir()
	{
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/banner/";
	}

    public function getTmpUploadRootDir()
	{
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadImage() {
        if (null === $this->image) {
            return;
        }

		if(is_object($this->image))
		{
			$fileName = basename($this->image->getClientOriginalName());
			$reverseFileName = strrev($fileName);
			$explodeFileName = explode(".", $reverseFileName, 2);
			$firstNameFile = strrev($explodeFileName[1]);
			$secondNameFile = strrev($explodeFileName[0]);
			$newNameFile = uniqid().'-'.$firstNameFile.".".$secondNameFile;
			if(!$this->id){
				$this->image->move($this->getTmpUploadRootDir(), $newNameFile);
			}else{
				if (is_object($this->image))
					$this->image->move($this->getUploadRootDir(), $newNameFile);
			}
			if (is_object($this->image))
				$this->setImage($newNameFile);
		} elseif(filter_var($this->image, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->image);
			$pi = pathinfo($this->image);
			$extension = $res = pathinfo(parse_url($this->image, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setLogo($filename);
		}
    }

    public function setDisplay($display)
    {
        $this->display = $display;
    
        return $this;
    }

    public function getDisplay()
    {
        return $this->display;
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