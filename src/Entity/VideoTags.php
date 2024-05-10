<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="videotags")
 */
class VideoTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Video")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Video::class;
	}
	
	public function getClassName()
	{
		return 'VideoTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Video $entity
     */
    public function setEntity(Video $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Video 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}