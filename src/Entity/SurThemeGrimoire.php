<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Service\APPurifierHTML;

/**
 * App\Entity\surThemeGrimoire
 *
 * @ORM\Table(name="surthemegrimoire")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\SurThemeGrimoireRepository")
 */
class SurThemeGrimoire
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
	 * @Assert\NotBlank(groups={"stg_validation"})
     */
    private $title;

    /**
     * @var string $theme
     *
     * @ORM\Column(name="theme", type="string", length=255, nullable=true)
	 * @Assert\NotBlank(groups={"stg_validation"})
     */
    private $theme;

    /**
     * @var text $text
     *
     * @ORM\Column(name="text", type="text", nullable=true)
	 * @Assert\NotBlank(groups={"stg_validation"})
     */
    private $text;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
	 * Assert\NotBlank(groups={"stg_validation"})
     */
    private $photo;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\MenuGrimoire")
	 * @Assert\NotBlank(groups={"stg_validation"})
     */
    private $menuGrimoire;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SurThemeGrimoire")
	 * @Assert\NotBlank(groups={"stg_validation"})
     */
    private $parentTheme;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
	 * @Assert\NotBlank(groups={"stg_validation"})
     */
    private $language;

    /**
     * @var string $internationalName
     *
     * @ORM\Column(name="internationalName", type="string", length=255)
	 * @Assert\NotBlank(groups={"stg_validation"})
     */
    private $internationalName;
	
	public function __toString()
	{
		return $this->title;
	}

	public function getMenuGrimoireTitle()
	{
		return !empty($parentTheme = $this->parentTheme) ? $parentTheme->getTitle() : null;
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
     * Set theme
     *
     * @param string $theme
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * Get theme
     *
     * @return string 
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set text
     *
     * @param text $text
     */
    public function setText($text)
    {
		$purifier = new APPurifierHTML();
		$this->text = $purifier->purifier($text);
        //$this->text = $text;
    }

    /**
     * Get text
     *
     * @return text 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set photo
     *
     * @param string $photo
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    /**
     * Get photo
     *
     * @return string 
     */
    public function getPhoto()
    {
        return $this->photo;
    }

	public function getFullPicturePath() {
        return null === $this->photo ? null : $this->getUploadRootDir(). $this->photo;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/witchcraft/surTheme/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadPhoto() {
        // the file property can be empty if the field is not required
        if (null === $this->photo) {
            return;
        }

		if(is_object($this->photo))
		{
			$NameFile = basename($this->photo->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->photo->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->photo))
					$this->photo->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->photo))
				$this->setPhoto($NewNameFile);
		} elseif(filter_var($this->photo, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->photo);
			$pi = pathinfo($this->photo);
			$extension = $res = pathinfo(parse_url($this->photo, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setPhoto($filename);
		}
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getMenuGrimoire()
    {
        return $this->menuGrimoire;
    }

    public function setMenuGrimoire(MenuGrimoire $menuGrimoire)
    {
        $this->menuGrimoire = $menuGrimoire;
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

    public function getParentTheme()
    {
        return $this->parentTheme;
    }

    public function setParentTheme(SurThemeGrimoire $parentTheme)
    {
        $this->parentTheme = $parentTheme;
    }
}