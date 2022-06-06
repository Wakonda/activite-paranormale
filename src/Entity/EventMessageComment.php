<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventMessageCommentRepository")
 * @ORM\Table(name="eventmessagecomment")
 */
class EventMessageComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\EventMessage")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return EventMessage::class;
	}
	
	public function getClassName()
	{
		return 'EventMessageComment';
	}

    /**
     * Set entity
     *
     * @param App\Entity\EventMessage $entity
     */
    public function setEntity(EventMessage $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\EventMessage 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}