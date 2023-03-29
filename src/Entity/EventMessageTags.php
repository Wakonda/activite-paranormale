<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\EventMessage;

/**
 * @ORM\Entity()
 * @ORM\Table(name="eventmessagetags")
 */
class EventMessageTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\EventMessage")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return EventMessage::class;
	}
	
	public function getClassName()
	{
		return 'EventMessageTags';
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