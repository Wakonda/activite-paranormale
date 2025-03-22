<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'webdirectoryseo')]
#[ORM\Entity(repositoryClass: 'App\Repository\WebDirectorySEORepository')]
class WebDirectorySEO
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'returnLink', type: 'text')]
    private $returnLink;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\Column(name: 'text', type: 'text')]
    private $text;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'link', type: 'string', length: 255)]
    private $link;

    public function getId()
    {
        return $this->id;
    }

    public function setReturnLink($returnLink)
    {
        $this->returnLink = $returnLink;
    }

    public function getReturnLink()
    {
        return $this->returnLink;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
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