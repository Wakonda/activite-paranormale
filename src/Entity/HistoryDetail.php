<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'historydetail')]
#[ORM\Entity(repositoryClass: 'App\Repository\HistoryDetailRepository')]
class HistoryDetail
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\ManyToOne(targetEntity: History::class, inversedBy: 'historyDetails')]
	#[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $history;

	#[ORM\Column(type: 'datetime')]
    protected $modificationDateTime;

	#[ORM\Column(type: 'array', nullable: true)]
	protected $diffText;

	#[ORM\Column(type: 'string', length: 255)]
    protected $ipAddress;

	#[ORM\Column(type: 'string', length: 255)]
    protected $user;

	public function __construct() {
		$this->modificationDateTime = new \DateTime();
	}

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

    public function setModificationDateTime($modificationDateTime)
    {
        $this->modificationDateTime = $modificationDateTime;
    }

    public function getModificationDateTime()
    {
        return $this->modificationDateTime;
    }

    public function setDiffText($diffText)
    {
		$this->diffText = $diffText;
    }

    public function getDiffText()
    {
        return $this->diffText;
    }

    public function setIpAddress($ipAddress)
    {
		$this->ipAddress = $ipAddress;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setUser($user)
    {
		$this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}