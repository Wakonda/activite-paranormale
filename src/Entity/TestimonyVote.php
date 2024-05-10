<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestimonyVoteRepository")
 * @ORM\Table(name="testimonyvote")
 */
class TestimonyVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Testimony")
	* @ORM\JoinColumn(name="testimony_id", nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Testimony::class;
	}
	
	public function getClassName()
	{
		return 'TestimonyVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity($entity) {
        $this->entity = $entity;
    }
}