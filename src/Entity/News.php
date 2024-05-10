<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\News
 *
 * @ORM\Table(name="news")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\NewsRepository")
 */
class News extends MappedSuperclassBase implements Interfaces\PhotoIllustrationInterface
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
     * @var text $abstractText
     *
     * @ORM\Column(name="abstractText", type="text", nullable=true)
     */
    private $abstractText;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

    /**
     * @var string $sourceNew
     *
     * @ORM\Column(name="sourceNew", type="text", nullable=true)
     */
    private $sourceNew;
	
	/**
     * @var string $leadNew
     *
     * @ORM\Column(name="leadNew", type="string", nullable=true)
     */
    private $leadNew;
	
	public function __construct()
	{
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
     * Set abstractText
     *
     * @param text $abstractText
     */
    public function setAbstractText($abstractText)
    {
        $this->abstractText = $abstractText;
    }

    /**
     * Get abstractText
     *
     * @return text 
     */
    public function getAbstractText()
    {
        return $this->abstractText;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath(): String
	{
		return "extended/photo/news/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    /**
     * Set sourceNew
     *
     * @param string $sourceNew
     */
    public function setSourceNew($sourceNew)
    {
        $this->sourceNew = $sourceNew;
    }

    /**
     * Get sourceNew
     *
     * @return string 
     */
    public function getSourceNew()
    {
        return $this->sourceNew;
    }
    
	/**
     * Set leadNew
     *
     * @param string $leadNew
     */
    public function setLeadNew($leadNew)
    {
        $this->leadNew = $leadNew;
    }

    /**
     * Get leadNew
     *
     * @return string 
     */
    public function getLeadNew()
    {
        return $this->leadNew;
    }

    /**
     * Set illustration
     *
     * @param string $illustration
     */
    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    /**
     * Get illustration
     *
     * @return string 
     */
    public function getIllustration()
    {
        return $this->illustration;
    }
}