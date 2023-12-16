<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Vote
 *
 * @ORM\Table(name="vote")
 * @ORM\Entity(repositoryClass="App\Repository\VoteRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
	"vote" = "Vote",
	"news_vote" = "NewsVote",
	"video_vote" = "VideoVote",
	"grimoire_vote" = "GrimoireVote",
	"testimony_vote" = "TestimonyVote",
	"photo_vote" = "PhotoVote",
	"book_vote" = "BookVote",
	"witchcraft_tool_vote" = "WitchcraftToolVote",
	"event_message_vote" = "EventMessageVote",
	"movie_vote" = "MovieVote",
	"televisionserie_vote" = "TelevisionSerieVote"
  })
 */
class Vote
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	/**
	 * @var string $idClassVote
	 *
	 * @ORM\Column(name="idClassVote", type="string", length=255, nullable=true)
	 */
	 private $idClassVote;
	 
	/**
	 * @var string $classNameVote
	 *
	 * @ORM\Column(name="classNameVote", type="string", length=255, nullable=true)
	 */
	 private $classNameVote;
	 
	/**
	 * @var string $valueVote
	 *
	 * @ORM\Column(name="valueVote", type="string", length=255, nullable=true)
	 */
	 private $valueVote;

    /**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $author;

    public function getId()
    {
        return $this->id;
    }

	/**
     * Set idClassVote
     *
     * @param string $idClassVote
     */
    public function setIdClassVote($idClassVote)
    {
        $this->idClassVote = $idClassVote;
    }

    /**
     * Get idClassVote
     *
     * @return string 
     */
    public function getIdClassVote()
    {
        return $this->idClassVote;
    }
	
	/**
     * Set classNameVote
     *
     * @param string $classNameVote
     */
    public function setClassNameVote($classNameVote)
    {
        $this->classNameVote = $classNameVote;
    }

    /**
     * Get classNameVote
     *
     * @return string 
     */
    public function getClassNameVote()
    {
        return $this->classNameVote;
    }
	
	/**
     * Set valueVote
     *
     * @param string $valueVote
     */
    public function setValueVote($valueVote)
    {
        $this->valueVote = $valueVote;
    }

    /**
     * Get valueVote
     *
     * @return string 
     */
    public function getValueVote()
    {
        return $this->valueVote;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }
}