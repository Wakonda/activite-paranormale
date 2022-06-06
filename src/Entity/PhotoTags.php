<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="phototags")
 */
class PhotoTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Photo")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Photo::class;
	}
	
	public function getClassName()
	{
		return 'PhotoTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Photo $entity
     */
    public function setEntity(Photo $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Photo 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}