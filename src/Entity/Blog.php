<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;

#[ORM\Table(name: 'blog')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\BlogRepository')]
#[ApiResource(
    normalizationContext: ['groups' => ['api_read']],
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
class Blog
{
	const WEBSITE_CATEGORY = "website";
	const FORUM_CATEGORY = "forum";
	const STORE_CATEGORY = "store";
	const BLOG_CATEGORY = "blog";

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
	#[Groups('api_read')]
    private $title;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $banner;

	#[ORM\Column(name: 'link', type: 'string', length: 255)]
	#[Groups('api_read')]
    private $link;

	#[ORM\Column(name: 'rss', type: 'string', length: 255, nullable: true)]
    private $rss;

	#[ORM\Column(name: 'text', type: 'text')]
	#[Groups('api_read')]
    private $text;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
	#[Groups('api_read')]
    private $language;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
	#[ORM\JoinColumn(name: 'languageOfBlog_id')]
	#[Groups('api_read')]
    private $languageOfBlog;

	#[ORM\Column(name: 'active', type: 'boolean')]
	private $active;

	#[ORM\Column(name: 'category', type: 'string')]
    private $category;
	
	public function __construct()
	{
		$this->active = true;
	}
		
	public function getAssetImagePath()
	{
		return "extended/photo/blog/";
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

    public function setBanner($banner)
    {
        $this->banner = $banner;
    }

    public function getBanner()
    {
        return $this->banner;
    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setRss($rss)
    {
        $this->rss = $rss;
    }

    public function getRss()
    {
        return $this->rss;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(\App\Entity\Language $language)
    {
        $this->language = $language;
    }

    public function getLanguageOfBlog()
    {
        return $this->languageOfBlog;
    }

    public function setLanguageOfBlog(\App\Entity\Language $languageOfBlog)
    {
        $this->languageOfBlog = $languageOfBlog;
    }

	public function getFullPicturePath() {
        return null === $this->banner ? null : $this->getUploadRootDir(). $this->banner;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadBanner() {
        // the file property can be empty if the field is not required
        if (null === $this->banner) {
            return;
        }

		if(is_object($this->banner))
		{
			$NameFile = basename($this->banner->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->banner->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->banner))
					$this->banner->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->banner))
				$this->setBanner($NewNameFile);
		} elseif(filter_var($this->banner, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->banner);
			$pi = pathinfo($this->banner);
			$extension = $res = pathinfo(parse_url($this->logo, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setBanner($filename);
		}
    }

	#[ORM\PostRemove]
	public function removeBanner()
	{
		$file = $this->getAssetImagePath().$this->banner;

		if(file_exists($file)) {
			unlink($file);
		}    
	}
		
	public function isActive()
	{
		return $this->active == true;
	}
	
	public function setActive($active)
	{
		$this->active = $active;
	}
	
	public function getActive()
	{
		return $this->active;
	}
	
	public function setCategory($category)
	{
		$this->category = $category;
	}
	
	public function getCategory()
	{
		return $this->category;
	}
}