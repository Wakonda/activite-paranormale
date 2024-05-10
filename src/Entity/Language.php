<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * App\Entity\Language
 *
 * @ORM\Table(name="language")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\LanguageRepository")
 */
class Language
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
	 * @Groups("api_read")
     */
    private $title;

    /**
     * @var string $abbreviation
     *
     * @ORM\Column(name="abbreviation", type="string", length=255)
	 * @Groups("api_read")
     */
    private $abbreviation;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $logo;

	/**
	 * @ORM\Column(name="direction", type="string", length=3)
	 *
	 */
	private $direction;

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
     * Set abbreviation
     *
     * @param string $abbreviation
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
    }

    /**
     * Get abbreviation
     *
     * @return string 
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * Set logo
     *
     * @param string $logo
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
    }

    /**
     * Get logo
     *
     * @return string 
     */
    public function getLogo()
    {
        return $this->logo;
    }
	
	/**
	 * Set direction
	 *
	 * @param string $direction
	 */
	public function setDirection($direction)
	{
		$this->direction = $direction;
	}
	
	/**
	 * Get direction
	 *
	 * @return string
	 */
	public function getDirection()
	{
		return $this->direction;
	}
	
	public function getFullPicturePath() {
        return null === $this->logo ? null : $this->getUploadRootDir(). $this->logo;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/language/";
	}
	
    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadIconeLangue() {
        // the file property can be empty if the field is not required
        if (null === $this->logo) {
            return;
        }

		if(is_object($this->logo))
		{
			$NameFile = basename($this->logo->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->logo->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->logo))
					$this->logo->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->logo))
				$this->setLogo($NewNameFile);
		} elseif(filter_var($this->logo, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->logo);
			$pi = pathinfo($this->logo);
			$extension = $res = pathinfo(parse_url($this->logo, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setLogo($filename);
		}
    }
}