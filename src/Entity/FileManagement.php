<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;

#[ORM\Table(name: 'filemanagement')]
#[ORM\Entity(repositoryClass: 'App\Repository\FileManagementRepository')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['filemanagement' => 'FileManagement', 'testimony_filemanagement' => 'TestimonyFileManagement'])]
class FileManagement
{
	const FILE_KIND = "file";
	const DRAWING_KIND = "drawing";

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'titleFile', type: 'string', length: 255)]
    private $titleFile;

	#[ORM\Column(name: 'realNameFile', type: 'string', length: 255)]
    private $realNameFile;

	#[ORM\Column(name: 'extensionFile', type: 'string', length: 255)]
    private $extensionFile;

	#[ORM\Column(name: 'kindFile', type: 'string', length: 255)]
    private $kindFile;

	#[ORM\Column(name: 'caption', type: 'text', nullable: true)]
    private $caption;

	#[ORM\Column(name: 'license', type: 'string', length: 255, nullable: true)]
    private $license;

	#[ORM\Column(name: 'author', type: 'string', length: 255, nullable: true)]
    private $author;

	#[ORM\Column(name: 'urlSource', type: 'string', length: 500, nullable: true)]
    private $urlSource;

    public function getId()
    {
        return $this->id;
    }
	
	public function __construct()
	{
		$this->kindFile = self::FILE_KIND;
	}

    public function setTitleFile($titleFile)
    {
        $this->titleFile = $titleFile;
    }

    public function getTitleFile()
    {
        return $this->titleFile;
    }

    public function setRealNameFile($realNameFile)
    {
        $this->realNameFile = $realNameFile;
    }

    public function getRealNameFile()
    {
        return $this->realNameFile;
    }

    public function setExtensionFile($extensionFile)
    {
        $this->extensionFile = $extensionFile;
    }

    public function getExtensionFile()
    {
        return $this->extensionFile;
    }

    public function setKindFile($kindFile)
    {
        $this->kindFile = $kindFile;
    }

    public function getKindFile()
    {
        return $this->kindFile;
    }

    public function setCaption($caption)
    {
        $this->caption = (new APPurifierHTML())->purifier($caption);
    }

    public function getCaption()
    {
        return $this->caption;
    }

    public function setLicense($license)
    {
        $this->license = $license;
    }

    public function getLicense()
    {
        return $this->license;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setUrlSource($urlSource)
    {
        $this->urlSource = $urlSource;
    }

    public function getUrlSource()
    {
        return $this->urlSource;
    }
}