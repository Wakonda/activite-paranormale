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
	const LOCALE_PLATFORM = "AP";
	const DAILYMOTION_PLATFORM = "Dailymotion";
	const FACEBOOK_PLATFORM = "Facebook";
	const INSTAGRAM_PLATFORM = "Instagram";
	const RUTUBE_PLATFORM = "Rutube";
	const TWITTER_PLATFORM = "Twitter";
	const YOUTUBE_PLATFORM = "Youtube";
	const OTHER_PLATFORM = "Other";

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

		if(is_numeric($width) and is_numeric($height) and !empty($width) and !empty($height))
			$height_max = round(($width_max * $height) / $width);

		$iframeNode->item(0)->setAttribute("height", $height_max);
		$iframeNode->item(0)->setAttribute("width", $width_max);
		
		return $dom->saveHTML();
	}

	public function getThumbnailVideo() {
		$code = $this->getEmbeddedCode();
		$platform = $this->getPlatformByCode($code);
		$pattern = '/<[^>]*>/';

		if ($platform == strtolower(self::YOUTUBE_PLATFORM)) {
			$pattern = '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/';
			if (preg_match($pattern, $code, $matches)) {
				$videoId = $matches[1];
				
				return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
			} else
				return null;
		} elseif ($platform == strtolower(self::DAILYMOTION_PLATFORM)) {
			$dom = new \DOMDocument();
			$dom->loadHTML($code);
			$iframe = $dom->getElementsByTagName('iframe')->item(0);

			if ($iframe) {
				$src = $iframe->getAttribute('src');
				$src = parse_url($src, PHP_URL_PATH);
				$videoId = substr($src, strrpos($src, '/') + 1);

				return "https://www.dailymotion.com/thumbnail/video/{$videoId}";
			}
		} elseif($platform == strtolower(self::RUTUBE_PLATFORM)) {
			$pattern = '/src="https:\/\/rutube\.ru\/play\/embed\/([^"]+)"/';
			if (preg_match($pattern, $code, $matches)) {
				$videoId = $matches[1];

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL,"https://rutube.ru/api/video/{$videoId}/thumbnail");
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); 
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

				$json = curl_exec($curl);
				$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

				curl_close($curl);

				if($httpCode == 200) {
					$json = json_decode($json);
					return $json->url;
				}
			}
		} else {
			return null;
		}

		return null;
	}

	public function getPlatformByCode() {
		$code = $this->getEmbeddedCode();
		$platform = null;
		$pattern = '/<[^>]*>/';

		if (preg_match($pattern, $code)) {
			$doc = new \DOMDocument();
			$doc->loadHTML($code);

			$iframe = $doc->getElementsByTagName('iframe')->item(0);

			if(!empty($iframe)) {
				$srcAttribute = $iframe->getAttribute('src');

				if (strpos($srcAttribute, 'youtube.com') !== false) {
					return strtolower(self::YOUTUBE_PLATFORM);
				} elseif (strpos($srcAttribute, 'dailymotion.com') !== false) {
					return strtolower(self::DAILYMOTION_PLATFORM);
				} elseif(strpos($srcAttribute, 'rutube.ru') !== false) {
					return strtolower(self::RUTUBE_PLATFORM);
				}
			}

			if (str_contains($code, "twitter"))
				return strtolower(self::TWITTER_PLATFORM);
		}

		return $platform;
	}

	public function getURLByCode() {
		$code = $this->getEmbeddedCode();
		$platform = null;
		$pattern = '/<[^>]*>/';

		if (preg_match($pattern, $code)) {
			$doc = new \DOMDocument();
			$doc->loadHTML($code);

			$iframe = $doc->getElementsByTagName('iframe')->item(0);

			if(empty($iframe))
				return null;

			return $iframe->getAttribute('src');
		}

		return $platform;
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
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/video/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadPhoto() {
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