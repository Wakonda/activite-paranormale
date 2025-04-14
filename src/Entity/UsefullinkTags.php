<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'usefullink_tags')]
#[ORM\Entity(repositoryClass: 'App\Repository\UsefullinkTagsRepository')]
class UsefullinkTags
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'string', length: 255)]
	#[Groups('api_read')]
    private $title;

	#[ORM\ManyToMany(targetEntity: 'App\Entity\UsefulLink', mappedBy: 'usefulLinkTags')]
    private $usefulLinks;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setUsefulLinks(string $usefulLinks)
    {
        $this->usefulLinks = $usefulLinks;

        return $this;
    }

    public function getUsefulLinks()
    {
        return $this->usefulLinks;
    }
}