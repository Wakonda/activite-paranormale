<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;

/**
 * App\Entity\FileManagement
 *
 * @ORM\Table(name="filemanagement")
 * @ORM\Entity(repositoryClass="App\Repository\FileManagementRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
	"filemanagement" = "FileManagement",
	"testimony_filemanagement" = "TestimonyFileManagement",
  })
 */
class FileManagement
{
	const FILE_KIND = "file";
	const DRAWING_KIND = "drawing";

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $titleFile
     *
     * @ORM\Column(name="titleFile", type="string", length=255)
     */
    private $titleFile;

    /**
     * @var string $realNameFile
     *
     * @ORM\Column(name="realNameFile", type="string", length=255)
     */
    private $realNameFile;

    /**
     * @var string $extensionFile
     *
     * @ORM\Column(name="extensionFile", type="string", length=255)
     */
    private $extensionFile;

    /**
     * @var string $kindFile
     *
     * @ORM\Column(name="kindFile", type="string", length=255)
     */
    private $kindFile;

    /**
     * @var text $caption
     *
     * @ORM\Column(name="caption", type="text", nullable=true)
     */
    private $caption;

    /**
     * @ORM\Column(name="license", type="string", length=255, nullable=true)
     */
    private $license;

    /**
     * @ORM\Column(name="author", type="string", length=255, nullable=true)
     */
    private $author;

    /**
     * @ORM\Column(name="urlSource", type="string", length=255, nullable=true)
     */
    private $urlSource;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
	
	public function __construct()
	{
		$this->kindFile = self::FILE_KIND;
	}

    /**
     * Set titleFile
     *
     * @param string $titleFile
     */
    public function setTitleFile($titleFile)
    {
        $this->titleFile = $titleFile;
    }

    /**
     * Get titleFile
     *
     * @return string 
     */
    public function getTitleFile()
    {
        return $this->titleFile;
    }

    /**
     * Set realNameFile
     *
     * @param string $realNameFile
     */
    public function setRealNameFile($realNameFile)
    {
        $this->realNameFile = $realNameFile;
    }

    /**
     * Get realNameFile
     *
     * @return string 
     */
    public function getRealNameFile()
    {
        return $this->realNameFile;
    }

    /**
     * Set extensionFile
     *
     * @param string $extensionFile
     */
    public function setExtensionFile($extensionFile)
    {
        $this->extensionFile = $extensionFile;
    }

    /**
     * Get extensionFile
     *
     * @return string 
     */
    public function getExtensionFile()
    {
        return $this->extensionFile;
    }

    /**
     * Set kindFile
     *
     * @param string $kindFile
     */
    public function setKindFile($kindFile)
    {
        $this->kindFile = $kindFile;
    }

    /**
     * Get kindFile
     *
     * @return string 
     */
    public function getKindFile()
    {
        return $this->kindFile;
    }
	
    /**
     * Set caption
     *
     * @param text $caption
     */
    public function setCaption($caption)
    {
        $this->caption = (new APPurifierHTML())->purifier($caption);
    }

    /**
     * Get caption
     *
     * @return text 
     */
    public function getCaption()
    {
        return $this->caption;
    }
	
    /**
     * @param string $license
     */
    public function setLicense($license)
    {
        $this->license = $license;
    }

    /**
     * @return string 
     */
    public function getLicense()
    {
        return $this->license;
    }
	
    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return string 
     */
    public function getAuthor()
    {
        return $this->author;
    }
	
    /**
     * @param string $urlSource
     */
    public function setUrlSource($urlSource)
    {
        $this->urlSource = $urlSource;
    }

    /**
     * @return string 
     */
    public function getUrlSource()
    {
        return $this->urlSource;
    }
}