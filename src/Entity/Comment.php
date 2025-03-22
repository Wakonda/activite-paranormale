<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'comment')]
#[ORM\Entity(repositoryClass: 'App\Repository\CommentRepository')]
#[ORM\InheritanceType("JOINED")]
#[ORM\DiscriminatorColumn(name: "discr", type: "string")]
#[ORM\DiscriminatorMap([
    "news_comment" => NewsComment::class,
    "video_comment" => VideoComment::class,
    "photo_comment" => PhotoComment::class,
    "grimoire_comment" => GrimoireComment::class,
    "testimony_comment" => TestimonyComment::class,
    "comment" => Comment::class,
    "book_comment" => BookComment::class,
    "witchcraft_tool_comment" => WitchcraftToolComment::class,
    "event_message_comment" => EventMessageComment::class,
    "cartography_comment" => CartographyComment::class,
    "movie_comment" => MovieComment::class,
    "document_comment" => DocumentComment::class,
    "televisionserie_comment" => TelevisionSerieComment::class
])]
class Comment
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'messageComment', type: 'text')]
    private $messageComment;

	#[ORM\Column(name: 'emailComment', type: 'string', length: 255, nullable: true)]
    private $emailComment;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
	#[ORM\JoinColumn(name: 'authorComment_id')]
    private $authorComment;

	#[ORM\Column(name: 'anonymousAuthorComment', type: 'string', length: 255)]
    private $anonymousAuthorComment;

	#[ORM\Column(name: 'anonymousComment', type: 'string', length: 255, nullable: true)]
    private $anonymousComment;

	#[ORM\Column(name: 'dateComment', type: 'datetime')]
    private $dateComment;

	#[ORM\Column(name: 'state', type: 'integer', length: 1, options: ["default" => 0])]
	private $state;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Comment')]
	#[ORM\JoinColumn(name: 'parentComment_id')]
    private $parentComment;

	public static $approved = 1;
	public static $denied = -1;
	public static $notChecked = 0;
	
	public function isApproved()
	{
		return $this->state == self::$approved;
	}
	
	public function isDenied()
	{
		return $this->state == self::$denied;
	}
	
	public function isNotChecked()
	{
		return $this->state == self::$notChecked;
	}
	
	public function getApproved()
	{
		return self::$approved;
	}

	public function getDenied()
	{
		return self::$denied;
	}
	
	public function getNotChecked()
	{
		return self::$notChecked;
	}
	
	public function setApproved()
	{
		$this->state = self::$approved;
	}
	
	public function setDenied()
	{
		$this->state = self::$denied;
	}
	
	public function setNotChecked()
	{
		$this->state = self::$notChecked;
	}
	
	public function __construct()
	{
		$this->dateComment = new \DateTime();
		$this->setNotChecked();
	}

    public function getId()
    {
        return $this->id;
    }

    public function setMessageComment($messageComment)
    {
        $this->messageComment = $messageComment;
    }

    public function getMessageComment()
    {
        return $this->messageComment;
    }

    public function setEmailComment($emailComment)
    {
        $this->emailComment = $emailComment;
    }

    public function getEmailComment()
    {
        return $this->emailComment;
    }

    public function setAuthorComment($authorComment)
    {
        $this->authorComment = $authorComment;
    }

    public function getAuthorComment()
    {
        return $this->authorComment;
    }

    public function setAnonymousAuthorComment($anonymousAuthorComment)
    {
        $this->anonymousAuthorComment = $anonymousAuthorComment;
    }

    public function getAnonymousAuthorComment()
    {
        return $this->anonymousAuthorComment;
    }

    public function setAnonymousComment($anonymousComment)
    {
        $this->anonymousComment = $anonymousComment;
    }

    public function getAnonymousComment()
    {
        return $this->anonymousComment;
    }

    public function setDateComment($dateComment)
    {
        $this->dateComment = $dateComment;
    }

    public function getDateComment()
    {
        return $this->dateComment;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getParentComment()
    {
        return $this->parentComment;
    }

    public function setParentComment(Comment $parentComment)
    {
        $this->parentComment = $parentComment;
    }
}