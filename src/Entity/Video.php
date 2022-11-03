<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Video
 *
 * @ORM\Table(name="video")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\VideoRepository")
 */
class Video extends MappedSuperclassBase
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
     * @var string $platform
     *
     * @ORM\Column(name="platform", type="string", length=255)
     */
    private $platform;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
	 private $mediaVideo;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

	/** @ORM\Column(type="text", nullable=true) */
	private $embeddedCode;

	/** @ORM\Column(type="string") */
	protected $duration;
	
	/** @ORM\Column(type="boolean") */
	private $available;


	public function __construct()
	{
		$this->available = true;
		parent::__construct();
	}

	public function getPdfVersionRoute()
	{
		return "Video_Pdfversion";
	}

	public function getShowRoute()
	{
		return "Video_Read";
	}

	public function resizeVideo()
	{
		if(empty($this->embeddedCode))
			return null;

		$dom = new \DomDocument();
		
		libxml_use_internal_errors(true); 
		$dom->loadHtml($this->embeddedCode);
		libxml_clear_errors();
		
		$iframeNode = $dom->getElementsByTagName("iframe");
		
		if($iframeNode->length == 0)
			return $this->embeddedCode;

		$width_max = 550;
		$height_max = 309;
		
		if(!is_object($iframeNode->item(0)))
			return null;
		
		$width = $iframeNode->item(0)->getAttribute("width");
		$height = $iframeNode->item(0)->getAttribute("height");
		
		if(!empty($width) and !empty($height))
			$height_max = round(($width_max * $height) / $width);

		$iframeNode->item(0)->setAttribute("height", $height_max);
		$iframeNode->item(0)->setAttribute("width", $width_max);
		
		return $dom->saveHTML();
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
     * Set platform
     *
     * @param string $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * Get platform
     *
     * @return string 
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set mediaVideo
     *
     * @param string $mediaVideo
     */
    public function setMediaVideo($mediaVideo)
    {
        $this->mediaVideo = $mediaVideo;
    }

    /**
     * Get mediaVideo
     *
     * @return string 
     */
    public function getMediaVideo()
    {
        return $this->mediaVideo;
    }

    public function getUploadRootDirMediaVideo() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetVideoPath()
	{
		return "extended/flash/Video/KAWAplayer_v1/videos/";
	}

    protected function getTmpUploadRootDirMediaVideo() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetVideoPath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadMediaVideo() {
        // the file property can be empty if the field is not required
        if (null === $this->mediaVideo) {
            return;
        }

		if(is_object($this->mediaVideo))
		{
			$NameFile = basename($this->mediaVideo->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->mediaVideo->move($this->getTmpUploadRootDirMediaVideo(), $NewNameFile);
			}else{
				if (is_object($this->mediaVideo))
					$this->mediaVideo->move($this->getUploadRootDirMediaVideo(), $NewNameFile);
			}
			if (is_object($this->mediaVideo))
				$this->setMediaVideo($NewNameFile);
		} elseif(filter_var($this->mediaVideo, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->mediaVideo);
			$pi = pathinfo($this->mediaVideo);
			$extension = $res = pathinfo(parse_url($this->mediaVideo, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setMediaVideo($filename);
		}
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/video/";
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
     * Set duration
     *
     * @param string $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * Get duration
     *
     * @return string 
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set embeddedCode
     *
     * @param text $embeddedCode
     */
    public function setEmbeddedCode($embeddedCode)
    {
        $this->embeddedCode = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $embeddedCode));
    }

    /**
     * Get embeddedCode
     *
     * @return text 
     */
    public function getEmbeddedCode()
    {
        return $this->embeddedCode;
    }
	
	/**
     * Set available
     *
     * @param text $available
     */
    public function setAvailable($available)
    {
        $this->available = $available;
    }

    /**
     * Get available
     *
     * @return text 
     */
    public function getAvailable()
    {
        return $this->available;
    }
	
	public function isAvailable()
	{
        return $this->available == 1;
	}
}