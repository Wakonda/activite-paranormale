<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * App\Entity\Theme
 *
 * @ORM\Table(name="theme")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\ThemeRepository")
 */
class Theme
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
     * @ORM\ManyToOne(targetEntity="App\Entity\SurTheme")
     */
    private $surTheme;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Theme")
     */
    private $parentTheme;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pdfTheme;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    private $language;

    /**
     * @var string $internationalName
     *
     * @ORM\Column(name="internationalName", type="string", length=255)
     */
    private $internationalName;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $text;

	/**
	 * @var string $wikidata
	 *
	 * @ORM\Column(name="wikidata", type="string", length=15, nullable=true)
	 */
	private $wikidata;

    /**
     * @ORM\Column(name="source", type="text", nullable=true)
     */
    private $source;

	public function getPropertyEntityForm()
	{
		return $this->title.' ('.$this->language->getAbbreviation().')';
	}
	
	public function __toString() {
		return $this->title;
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
     * Set pdfTheme
     *
     * @param string $pdfTheme
     */
    public function setPdfTheme($pdfTheme)
    {
        $this->pdfTheme = $pdfTheme;
    }

    /**
     * Get pdfTheme
     *
     * @return string 
     */
    public function getPdfTheme()
    {
        return $this->pdfTheme;
    }
	
	public function getFullPicturePath() {
        return null === $this->pdfTheme ? null : $this->getUploadRootDir(). $this->pdfTheme;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/theme/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadPdfTheme() {
        // the file property can be empty if the field is not required
        if (null === $this->pdfTheme) {
            return;
        }

		if(is_object($this->pdfTheme))
		{
			$NameFile = basename($this->pdfTheme->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->pdfTheme->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->pdfTheme))
					$this->pdfTheme->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->pdfTheme))
				$this->setPdfTheme($NewNameFile);
		} elseif(filter_var($this->pdfTheme, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->pdfTheme);
			$pi = pathinfo($this->pdfTheme);
			$extension = $res = pathinfo(parse_url($this->pdfTheme, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setPdfTheme($filename);
		}
    }

    public function getSurTheme()
    {
        return $this->surTheme;
    }

    public function setSurTheme(SurTheme $surTheme)
    {
        $this->surTheme = $surTheme;
    }

    public function getParentTheme()
    {
        return $this->parentTheme;
    }

    public function setParentTheme(Theme $parentTheme)
    {
        $this->parentTheme = $parentTheme;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
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

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadPhotoTheme() {
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

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }
}