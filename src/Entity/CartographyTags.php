<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="cartographytags")
 */
class CartographyTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Cartography")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Cartography::class;
	}
	
	public function getClassName()
	{
		return 'CartographyTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Cartography $entity
     */
    public function setEntity(Cartography $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Cartography 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}