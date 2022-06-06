<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="testimonytags")
 */
class TestimonyTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Testimony")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Testimony::class;
	}
	
	public function getClassName()
	{
		return 'TestimonyTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Testimony $entity
     */
    public function setEntity(Testimony $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Testimony 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}