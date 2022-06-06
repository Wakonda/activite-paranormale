<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Contact
 *
 * @ORM\Table(name="contact")
 * @ORM\Entity(repositoryClass="App\Repository\ContactRepository")
 */
class Contact
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
     * @var string $pseudoContact
     *
     * @ORM\Column(name="pseudoContact", type="string", length=255)
     */
    private $pseudoContact;

    /**
     * @var string $emailContact
     *
     * @ORM\Column(name="emailContact", type="string", length=255)
     */
    private $emailContact;

    /**
     * @var string $subjectContact
     *
     * @ORM\Column(name="subjectContact", type="string", length=255)
     */
    private $subjectContact;

    /**
     * @var text $messageContact
     *
     * @ORM\Column(name="messageContact", type="text")
     */
    private $messageContact;
	
	/**
     * @var datetime $dateContact
     *
     * @ORM\Column(name="dateContact", type="datetime", nullable=true)
     */
    private $dateContact;

	/**
	 * @var string $stateContact
	 *
	 * @ORM\Column(name="stateContact", type="boolean", nullable=true)
	 */
	private $stateContact;

	public function __construct()
	{
		$this->dateContact = new \DateTime();
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
     * Set pseudoContact
     *
     * @param string $pseudoContact
     */
    public function setPseudoContact($pseudoContact)
    {
        $this->pseudoContact = $pseudoContact;
    }

    /**
     * Get pseudoContact
     *
     * @return string 
     */
    public function getPseudoContact()
    {
        return $this->pseudoContact;
    }

    /**
     * Set emailContact
     *
     * @param string $emailContact
     */
    public function setEmailContact($emailContact)
    {
        $this->emailContact = $emailContact;
    }

    /**
     * Get emailContact
     *
     * @return string 
     */
    public function getEmailContact()
    {
        return $this->emailContact;
    }

    /**
     * Set subjectContact
     *
     * @param string $subjectContact
     */
    public function setSubjectContact($subjectContact)
    {
        $this->subjectContact = $subjectContact;
    }

    /**
     * Get subjectContact
     *
     * @return string 
     */
    public function getSubjectContact()
    {
        return $this->subjectContact;
    }

    /**
     * Set messageContact
     *
     * @param text $messageContact
     */
    public function setMessageContact($messageContact)
    {
        $this->messageContact = $messageContact;
    }

    /**
     * Get messageContact
     *
     * @return text 
     */
    public function getMessageContact()
    {
        return $this->messageContact;
    }
	
	/**
     * Set dateContact
     *
     * @param datetime $dateContact
     */
    public function setDateContact($dateContact)
    {
        $this->dateContact = $dateContact;
    }

    /**
     * Get dateContact
     *
     * @return datetime 
     */
    public function getDateContact()
    {
        return $this->dateContact;
    }
	
	/**
     * Set stateContact
     *
     * @param string $stateContact
     */
    public function setStateContact($stateContact)
    {
        $this->stateContact = $stateContact;
    }

    /**
     * Get stateContact
     *
     * @return string 
     */
    public function getStateContact()
    {
        return $this->stateContact;
    }
}