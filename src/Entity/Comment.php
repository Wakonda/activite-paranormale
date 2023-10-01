<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\Comment
 *
 * @ORM\Table(name="comment")
 * @ORM\Entity(repositoryClass="App\Repository\CommentRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"news_comment" = "NewsComment", "video_comment" = "VideoComment", "photo_comment" = "PhotoComment", "grimoire_comment" = "GrimoireComment", "testimony_comment" = "TestimonyComment", "comment" = "Comment", "book_comment" = "BookComment", "witchcraft_tool_comment" = "WitchcraftToolComment", "event_message_comment" = "EventMessageComment", "cartography_comment" = "CartographyComment", "movie_comment" = "MovieComment", "document_comment" = "DocumentComment", "televisionserie_comment" = "TelevisionSerieComment"})
 */
class Comment
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
     * @var text $messageComment
     *
     * @ORM\Column(name="messageComment", type="text")
     */
    private $messageComment;

    /**
     * @var string $emailComment
     *
     * @ORM\Column(name="emailComment", type="string", length=255, nullable=true)
     */
    private $emailComment;

    /**
	 * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $authorComment;

    /**
     * @var string $anonymousAuthorComment
     *
     * @ORM\Column(name="anonymousAuthorComment", type="string", length=255)
     */
    private $anonymousAuthorComment;

    /**
     * @var string $anonymousComment
     *
     * @ORM\Column(name="anonymousComment", type="string", length=255, nullable=true)
     */
    private $anonymousComment;
	
	/**
     * @var datetime $dateComment
     *
     * @ORM\Column(name="dateComment", type="datetime")
     */
    private $dateComment;
	
	/**
	 * @var integer state
	 *
	 * @ORM\Column(name="state", type="integer", length=1, options={"default" = 0})
	 *
	 */
	private $state;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Comment")
     */
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
     * Set messageComment
     *
     * @param text $messageComment
     */
    public function setMessageComment($messageComment)
    {
        $this->messageComment = $messageComment;
    }

    /**
     * Get messageComment
     *
     * @return text 
     */
    public function getMessageComment()
    {
        return $this->messageComment;
    }

    /**
     * Set emailComment
     *
     * @param string $emailComment
     */
    public function setEmailComment($emailComment)
    {
        $this->emailComment = $emailComment;
    }

    /**
     * Get emailComment
     *
     * @return string 
     */
    public function getEmailComment()
    {
        return $this->emailComment;
    }

    /**
     * Set authorComment
     *
     * @param string $authorComment
     */
    public function setAuthorComment($authorComment)
    {
        $this->authorComment = $authorComment;
    }

    /**
     * Get authorComment
     *
     * @return string 
     */
    public function getAuthorComment()
    {
        return $this->authorComment;
    }

    /**
     * Set anonymousAuthorComment
     *
     * @param string $anonymousAuthorComment
     */
    public function setAnonymousAuthorComment($anonymousAuthorComment)
    {
        $this->anonymousAuthorComment = $anonymousAuthorComment;
    }

    /**
     * Get anonymousAuthorComment
     *
     * @return string 
     */
    public function getAnonymousAuthorComment()
    {
        return $this->anonymousAuthorComment;
    }

    /**
     * Set anonymousComment
     *
     * @param string $anonymousComment
     */
    public function setAnonymousComment($anonymousComment)
    {
        $this->anonymousComment = $anonymousComment;
    }

    /**
     * Get anonymousComment
     *
     * @return string 
     */
    public function getAnonymousComment()
    {
        return $this->anonymousComment;
    }
	
    /**
     * Set dateComment
     *
     * @param string $dateComment
     */
    public function setDateComment($dateComment)
    {
        $this->dateComment = $dateComment;
    }

    /**
     * Get dateComment
     *
     * @return string 
     */
    public function getDateComment()
    {
        return $this->dateComment;
    }

    /**
     * Set state
     *
     * param integer $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get state
     *
     * return integer 
     */
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