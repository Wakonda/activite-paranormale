<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EventMessageVoteRepository")
 * @ORM\Table(name="eventmessagevote")
 */
class EventMessageVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\EventMessage")
	* @ORM\JoinColumn(name="eventMessage_id", nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return EventMessage::class;
	}

	public function getClassName()
	{
		return 'EventMessageVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity($entity) {
        $this->entity = $entity;
    }
}