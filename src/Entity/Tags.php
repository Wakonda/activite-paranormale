<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'tags')]
#[ORM\Entity(repositoryClass: 'App\Repository\TagsRepository')]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap([
    "document_tags" => DocumentTags::class,
    "cartography_tags" => CartographyTags::class,
    "news_tags" => NewsTags::class,
    "video_tags" => VideoTags::class,
    "testimony_tags" => TestimonyTags::class,
    "photo_tags" => PhotoTags::class,
    "tags" => Tags::class,
    "movie_tags" => MovieTags::class,
    "televisionserie_tags" => TelevisionSerieTags::class,
    "book_tags" => BookTags::class,
    "episodetelevisionserie_tags" => EpisodeTelevisionSerieTags::class,
    "creepystory_tags" => CreepyStoryTags::class,
    "eventmessage_tags" => EventMessageTags::class
])]
class Tags
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'idClass', type: 'integer')]
    private $idClass;

	#[ORM\Column(name: 'nameClass', type: 'string', length: 255)]
    private $nameClass;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\TagWord')]
	#[ORM\JoinColumn(name: 'tagword_id', referencedColumnName: 'id', nullable: true)]
    private $tagWord;

    public function getId()
    {
        return $this->id;
    }

    public function setIdClass($idClass)
    {
        $this->idClass = $idClass;

        return $this;
    }

    public function getIdClass()
    {
        return $this->idClass;
    }

    public function setNameClass($nameClass)
    {
        $this->nameClass = $nameClass;

        return $this;
    }

    public function getNameClass()
    {
        return $this->nameClass;
    }

    public function setTagWord($tagWord)
    {
        $this->tagWord = $tagWord;

        return $this;
    }

    public function getTagWord()
    {
        return $this->tagWord;
    }
}