<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'vote')]
#[ORM\Entity(repositoryClass: 'App\Repository\VoteRepository')]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap([
	"vote" => "Vote",
	"news_vote" => "NewsVote",
	"video_vote" => "VideoVote",
	"grimoire_vote" => "GrimoireVote",
	"testimony_vote" => "TestimonyVote",
	"photo_vote" => "PhotoVote",
	"book_vote" => "BookVote",
	"witchcraft_tool_vote" => "WitchcraftToolVote",
	"event_message_vote" => "EventMessageVote",
	"movie_vote" => "MovieVote",
	"televisionserie_vote" => "TelevisionSerieVote",
	"classifiedads_vote" => "ClassifiedAdsVote"
])]
class Vote
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'idClassVote', type: 'string', length: 255, nullable: true)]
	 private $idClassVote;

	#[ORM\Column(name: 'classNameVote', type: 'string', length: 255, nullable: true)]
	 private $classNameVote;

	#[ORM\Column(name: 'valueVote', type: 'string', length: 255, nullable: true)]
	 private $valueVote;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private $author;

	#[ORM\Column(name: 'favorite', type: 'boolean', nullable: true)]
    private $favorite;

    public function getId()
    {
        return $this->id;
    }

    public function setIdClassVote($idClassVote)
    {
        $this->idClassVote = $idClassVote;
    }

    public function getIdClassVote()
    {
        return $this->idClassVote;
    }

    public function setClassNameVote($classNameVote)
    {
        $this->classNameVote = $classNameVote;
    }

    public function getClassNameVote()
    {
        return $this->classNameVote;
    }

    public function setValueVote($valueVote)
    {
        $this->valueVote = $valueVote;
    }

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

    public function setFavorite($favorite)
    {
        $this->favorite = $favorite;
    }

    public function getFavorite()
    {
        return $this->favorite;
    }
}