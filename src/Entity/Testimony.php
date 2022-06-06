<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Testimony
 *
 * @ORM\Table(name="testimony")
 * @ORM\Entity(repositoryClass="App\Repository\TestimonyRepository")
 */
class Testimony extends MappedSuperclassBase
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
     * @var string $emailAuthor
     *
     * @ORM\Column(name="emailAuthor", type="string", length=255, nullable=true)
     */
    private $emailAuthor;
	
	public function __construct()
	{
		parent::__construct();
	}

	public function getPdfVersionRoute()
	{
		return "Testimony_Pdfversion";
	}

	public function getShowRoute()
	{
		return "Testimony_Show";
	}

	public function getWaitingRoute()
	{
		return "Testimony_Waiting";
	}

	public function getAssetImagePath()
	{
		return "extended/photo/testimony/";
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

    public function setEmailAuthor($emailAuthor)
    {
        $this->emailAuthor = $emailAuthor;
    }

    public function getEmailAuthor()
    {
        return $this->emailAuthor;
    }
}