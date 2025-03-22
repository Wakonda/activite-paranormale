<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'news')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\NewsRepository')]
class News extends MappedSuperclassBase implements Interfaces\PhotoIllustrationInterface
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'abstractText', type: 'text', nullable: true)]
    private $abstractText;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(name: 'sourceNew', type: 'text', nullable: true)]
    private $sourceNew;

	#[ORM\Column(name: 'leadNew', type: 'string', nullable: true)]
    private $leadNew;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

	public function getPdfVersionRoute()
	{
		return "News_Pdfversion";
	}

	public function getShowRoute()
	{
		return "News_ReadNews_New";
	}
	
	public function getWaitingRoute()
	{
		return "News_Waiting";
	}

    public function getId()
    {
        return $this->id;
    }

    public function setAbstractText($abstractText)
    {
        $this->abstractText = $abstractText;
    }

    public function getAbstractText()
    {
        return $this->abstractText;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath(): String
	{
		return "extended/photo/news/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    public function setSourceNew($sourceNew)
    {
        $this->sourceNew = $sourceNew;
    }

    public function getSourceNew()
    {
        return $this->sourceNew;
    }

    public function setLeadNew($leadNew)
    {
        $this->leadNew = $leadNew;
    }

    public function getLeadNew()
    {
        return $this->leadNew;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }
}