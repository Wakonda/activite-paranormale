<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\VideoVoteRepository')]
#[ORM\Table(name: 'videovote')]
class VideoVote extends Vote
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Video')]
	#[ORM\JoinColumn(name: 'video_id', nullable: false)]
    private $entity;

	public function getMainEntityClassName() {
		return Video::class;
	}
	
	public function getClassName() {
		return 'VideoVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity($entity) {
        $this->entity = $entity;
    }
}