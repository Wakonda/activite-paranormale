<?php

namespace App\Entity\Stores;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AlbumStore extends Store {
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Album")
     */
    protected $album;

	public function getAlbum()
    {
        return $this->album;
    }

    public function setAlbum($album)
    {
        $this->album = $album;
    }
	
	public function __construct()
	{
		$this->setCategory(Store::ALBUM_CATEGORY);
	}
	
	public function getLinkedEntityName() {
		return $this->album->getRealClass();
	}
}