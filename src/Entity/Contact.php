<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'contact')]
#[ORM\Entity(repositoryClass: 'App\Repository\ContactRepository')]
class Contact
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'pseudoContact', type: 'string', length: 255, nullable: true)]
    private $pseudoContact;

	#[ORM\Column(name: 'emailContact', type: 'string', length: 255, nullable: true)]
    private $emailContact;

	#[ORM\Column(name: 'phoneNumber', type: 'encrypt', length: 255, nullable: true)]
    private $phoneNumber;

	#[ORM\Column(name: 'subjectContact', type: 'string', length: 255)]
    private $subjectContact;

	#[ORM\Column(name: 'messageContact', type: 'text')]
    private $messageContact;

	#[ORM\Column(name: 'dateContact', type: 'datetime', nullable: true)]
    private $dateContact;

	#[ORM\Column(name: 'stateContact', type: 'boolean', nullable: true)]
	private $stateContact;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
	#[ORM\JoinColumn(name: 'sender_id', referencedColumnName: 'id', nullable: true)]
    protected $sender;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
	#[ORM\JoinColumn(name: 'recipient_id', referencedColumnName: 'id', nullable: true)]
    protected $recipient;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Contact')]
    protected $initialMessage;

	public function __construct()
	{
		$this->dateContact = new \DateTime();
	}

	public function getSenderString() {
		return !empty($this->sender) ? $this->sender->getUsername() : $this->pseudoContact;
	}

	public function isPrivateMessage() {
		return !empty($this->getSenderString()) and !empty($this->recipient);
	}

    public function getId()
    {
        return $this->id;
    }

    public function setPseudoContact($pseudoContact)
    {
        $this->pseudoContact = $pseudoContact;
    }

    public function getPseudoContact()
    {
        return $this->pseudoContact;
    }

    public function setEmailContact($emailContact)
    {
        $this->emailContact = $emailContact;
    }

    public function getEmailContact()
    {
        return $this->emailContact;
    }

    public function setSubjectContact($subjectContact)
    {
        $this->subjectContact = $subjectContact;
    }

    public function getSubjectContact()
    {
        return $this->subjectContact;
    }

    public function setMessageContact($messageContact)
    {
        $this->messageContact = $messageContact;
    }

    public function getMessageContact()
    {
        return $this->messageContact;
    }

    public function setDateContact($dateContact)
    {
        $this->dateContact = $dateContact;
    }

    public function getDateContact()
    {
        return $this->dateContact;
    }

    public function setStateContact($stateContact)
    {
        $this->stateContact = $stateContact;
    }

    public function getStateContact()
    {
        return $this->stateContact;
    }

    public function setSender(User $sender)
    {
        $this->sender = $sender;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public function setRecipient(User $recipient)
    {
        $this->recipient = $recipient;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function setInitialMessage($initialMessage)
    {
        $this->initialMessage = $initialMessage;
    }

    public function getInitialMessage()
    {
        return $this->initialMessage;
    }

    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }
}