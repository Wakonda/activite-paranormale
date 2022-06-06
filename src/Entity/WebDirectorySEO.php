<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\WebDirectorySEO
 *
 * @ORM\Table(name="webdirectoryseo")
 * @ORM\Entity(repositoryClass="App\Repository\WebDirectorySEORepository")
 */
class WebDirectorySEO
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
     * @var string $returnLink
     *
     * @ORM\Column(name="returnLink", type="text")
     */
    private $returnLink;

    /**
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

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
     * Set returnLink
     *
     * @param string $returnLink
     */
    public function setReturnLink($returnLink)
    {
        $this->returnLink = $returnLink;
    }

    /**
     * Get returnLink
     *
     * @return string 
     */
    public function getReturnLink()
    {
        return $this->returnLink;
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