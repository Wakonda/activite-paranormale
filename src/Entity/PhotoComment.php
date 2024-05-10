<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PhotoCommentRepository")
 * @ORM\Table(name="photocomment")
 */
class PhotoComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Photo")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Photo::class;
	}
	
	public function getClassName()
	{
		return 'PhotoComment';
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