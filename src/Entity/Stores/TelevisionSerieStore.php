<?php

namespace App\Entity\Stores;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 */
class TelevisionSerieStore extends Store {
	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Movies\TelevisionSerie")
     */
    protected $televisionSerie;

	public function getTelevisionSerie()
    {
        return $this->televisionSerie;
    }

    public function setTelevisionSerie($televisionSerie)
    {
        $this->televisionSerie = $televisionSerie;
    }
	
	public function __construct()
	{
		$this->setCategory(Store::TELEVISION_SERIE_CATEGORY);
	}
	
	public function getLinkedEntityName() {
		return $this->televisionSerie->getRealClass();
	}
}