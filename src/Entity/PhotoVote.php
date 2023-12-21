<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PhotoVoteRepository")
 * @ORM\Table(name="photovote")
 */
class PhotoVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Photo")
	* @ORM\JoinColumn(name="photo_id", nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Photo::class;
	}

	public function getClassName()
	{
		return 'PhotoVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity($entity) {
        $this->entity = $entity;
    }
}