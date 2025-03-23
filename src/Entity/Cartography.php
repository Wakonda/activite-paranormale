<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'cartography')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\CartographyRepository')]
class Cartography extends MappedSuperclassBase implements Interfaces\PhotoIllustrationInterface
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	// Latitude
	#[ORM\Column(name: 'coordXMap', type: 'string', length: 255)]
    private $coordXMap;

	// Longitude
	#[ORM\Column(name: 'coordYMap', type: 'string', length: 255)]
    private $coordYMap;

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $photo;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(name: 'linkGMaps', type: 'string', length: 255, nullable: true)]
    private $linkGMaps;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
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

    public function getId()
    {
        return $this->id;
    }

    public function setCoordXMap($coordXMap)
    {
        $this->coordXMap = $coordXMap;
    }

    public function getCoordXMap()
    {
        return $this->coordXMap;
    }

    public function setCoordYMap($coordYMap)
    {
        $this->coordYMap = $coordYMap;
    }

    public function getCoordYMap()
    {
        return $this->coordYMap;
    }

    public function setLinkGMaps($linkGMaps)
    {
        $this->linkGMaps = $linkGMaps;
    }

    public function getLinkGMaps()
    {
        return $this->linkGMaps;
    }

    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

	public function getFullPicturePath() {
        return null === $this->photo ? null : $this->getUploadRootDir(). $this->photo;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/cartography/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    public function getIllustration()
    {
        return $this->illustration;
    }

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }
}