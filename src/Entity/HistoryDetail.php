<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * HistoryDetail
 *
 * @ORM\Table(name="historydetail")
 * @ORM\Entity(repositoryClass="App\Repository\HistoryDetailRepository")
 */
class HistoryDetail
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
    * @ORM\ManyToOne(targetEntity=History::class, inversedBy="historyDetails")
    * @ORM\JoinColumn(onDelete="CASCADE")
    */
    protected $history;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $modificationDateTime;

	/**
	 * @ORM\Column(type="array", nullable=true)
	 */
	protected $diffText;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $ipAddress;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $user;

	public function __construct() {
		$this->modificationDateTime = new \DateTime();
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

    public function setHistory($history)
    {
        $this->history = $history;
    
        return $this;
    }

    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Set modificationDateTime
     *
     * @param datetime $modificationDateTime
     */
    public function setModificationDateTime($modificationDateTime)
    {
        $this->modificationDateTime = $modificationDateTime;
    }

    /**
     * Get modificationDateTime
     *
     * @return datetime
     */
    public function getModificationDateTime()
    {
        return $this->modificationDateTime;
    }

    /**
     * Set diffText
     *
     * @param text $diffText
     */
    public function setDiffText($diffText)
    {
		$this->diffText = $diffText;
    }

    /**
     * Get diffText
     *
     * @return text 
     */
    public function getDiffText()
    {
        return $this->diffText;
    }

    /**
     * Set ipAddress
     *
     * @param text $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
		$this->ipAddress = $ipAddress;
    }

    /**
     * Get ipAddress
     *
     * @return text 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set user
     *
     * @param text $user
     */
    public function setUser($user)
    {
		$this->user = $user;
    }

    /**
     * Get user
     *
     * @return text 
     */
    public function getUser()
    {
        return $this->user;
    }
}