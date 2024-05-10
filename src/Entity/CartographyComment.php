<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CartographyCommentRepository")
 * @ORM\Table(name="cartographycomment")
 */
class CartographyComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Cartography")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Cartography::class;
	}
	
	public function getClassName()
	{
		return 'CartographyComment';
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