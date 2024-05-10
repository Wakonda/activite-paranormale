<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * App\Entity\Cartography
 *
 * @ORM\Table(name="cartography")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\CartographyRepository")
 */
class Cartography extends MappedSuperclassBase implements Interfaces\PhotoIllustrationInterface
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

	// Latitude
    /**
     * @var string $coordXMap
     *
     * @ORM\Column(name="coordXMap", type="string", length=255)
     */
    private $coordXMap;

	// Longitude
    /**
     * @var string $coordYMap
     *
     * @ORM\Column(name="coordYMap", type="string", length=255)
     */
    private $coordYMap;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

    /**
     * @var string $linkGMaps
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $linkGMaps;

	/**
	 * @var string $wikidata
	 *
	 * @ORM\Column(name="wikidata", type="string", length=15, nullable=true)
	 */
	private $wikidata;

	public function __construct()
	{
		parent::__construct();
	}
	
	public function __clone()
	{
		if($this->illustration)
			$this->illustration = clone $this->illustration;
	}

	public function getShowRoute()
	{
		return "Cartography_Show";
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
     * Set coordXMap
     *
     * @param string $coordXMap
     */
    public function setCoordXMap($coordXMap)
    {
        $this->coordXMap = $coordXMap;
    }

    /**
     * Get coordXMap
     *
     * @return string 
     */
    public function getCoordXMap()
    {
        return $this->coordXMap;
    }

    /**
     * Set coordYMap
     *
     * @param string $coordYMap
     */
    public function setCoordYMap($coordYMap)
    {
        $this->coordYMap = $coordYMap;
    }

    /**
     * Get coordYMap
     *
     * @return string 
     */
    public function getCoordYMap()
    {
        return $this->coordYMap;
    }

    /**
     * Set linkGMaps
     *
     * @param string $linkGMaps
     */
    public function setLinkGMaps($linkGMaps)
    {
        $this->linkGMaps = $linkGMaps;
    }

    /**
     * Get linkGMaps
     *
     * @return string 
     */
    public function getLinkGMaps()
    {
        return $this->linkGMaps;
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
		return "extended/photo/cartography/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
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

    /**
     * Set wikidata
     *
     * @param String $wikidata
     */
    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    /**
     * Get wikidata
     *
     * @return String
     */
    public function getWikidata()
    {
        return $this->wikidata;
    }
}