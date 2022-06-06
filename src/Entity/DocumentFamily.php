<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\DocumentFamily
 *
 * @ORM\Table(name="documentfamily")
 * @ORM\Entity(repositoryClass="App\Repository\DocumentFamilyRepository")
 */
class DocumentFamily
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
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

	/**
	 * @var string $internationalName
	 *
	 * @ORM\Column(name="internationalName", type="string", length=255)
	 */
	 private $internationalName;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    private $language;

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
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }    
	
	/**
     * Set internationalName
     *
     * @param string $internationalName
     */
    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    /**
     * Get internationalName
     *
     * @return string 
     */
    public function getInternationalName()
    {
        return $this->internationalName;
    }

    // On d�finit le getter et le setter associ�.
    public function getLanguage()
    {
        return $this->language;
    }

    // Ici, on force le type de l'argument � �tre une instance de notre entit� langue.
    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }
}