<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tags
 *
 * @ORM\Table(name="tags")
 * @ORM\Entity(repositoryClass="App\Repository\TagsRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
	"document_tags" = "DocumentTags",
	"cartography_tags" = "CartographyTags",
	"news_tags" = "NewsTags",
	"video_tags" = "VideoTags",
	"testimony_tags" = "TestimonyTags",
	"photo_tags" = "PhotoTags",
	"tags" = "Tags",
	"movie_tags" = "MovieTags",
	"televisionserie_tags" = "TelevisionSerieTags",
	"book_tags" = "BookTags",
	"episodetelevisionserie_tags" = "EpisodeTelevisionSerieTags",
	"creepystory_tags" = "CreepyStoryTags",
	"eventmessage_tags" = "EventMessageTags"
  })
 */
class Tags
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="idClass", type="integer")
     */
    private $idClass;

    /**
     * @var string
     *
     * @ORM\Column(name="nameClass", type="string", length=255)
     */
    private $nameClass;

    /**
	 * @ORM\ManyToOne(targetEntity="App\Entity\TagWord")
	 * @ORM\JoinColumn(name="tagword_id", referencedColumnName="id", nullable=true)
     */
    private $tagWord;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idClass
     *
     * @param integer $idClass
     * @return Tags
     */
    public function setIdClass($idClass)
    {
        $this->idClass = $idClass;

        return $this;
    }

    /**
     * Get idClass
     *
     * @return integer 
     */
    public function getIdClass()
    {
        return $this->idClass;
    }

    /**
     * Set nameClass
     *
     * @param string $nameClass
     * @return Tags
     */
    public function setNameClass($nameClass)
    {
        $this->nameClass = $nameClass;

        return $this;
    }

    /**
     * Get nameClass
     *
     * @return string 
     */
    public function getNameClass()
    {
        return $this->nameClass;
    }

    /**
     * Set tagWord
     *
     * @param string $tagWord
     * @return TagWord
     */
    public function setTagWord($tagWord)
    {
        $this->tagWord = $tagWord;

        return $this;
    }

    /**
     * Get tagWord
     *
     * @return TagWord
     */
    public function getTagWord()
    {
        return $this->tagWord;
    }
}
