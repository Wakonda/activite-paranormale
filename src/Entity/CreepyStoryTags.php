<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="creepystorytags")
 */
class CreepyStoryTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\CreepyStory")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return CreepyStory::class;
	}
	
	public function getClassName()
	{
		return 'CreepyStoryTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\CreepyStory $entity
     */
    public function setEntity(CreepyStory $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\CreepyStory
     */
    public function getEntity()
    {
        return $this->entity;
    }
}