<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventMessageVoteRepository")
 * @ORM\Table(name="eventmessagevote")
 */
class EventMessageVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\EventMessage")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $eventMessage;

	public function getMainEntityClassName()
	{
		return EventMessage::class;
	}
	
	public function getClassName()
	{
		return 'EventMessageVote';
	}

    /**
     * Set eventMessage
     *
     * @param App\Entity\EventMessage $eventMessage
     */
    public function setEventMessage(EventMessage $eventMessage)
    {
        $this->eventMessage = $eventMessage;
    }

    /**
     * Get eventMessage
     *
     * @return App\Entity\EventMessage
     */
    public function getEventMessage()
    {
        return $this->eventMessage;
    }
}