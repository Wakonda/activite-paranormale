<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\ClassifiedAdsVoteRepository')]
#[ORM\Table(name: 'classifiedadsvote')]
class ClassifiedAdsVote extends Vote
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\ClassifiedAds')]
	#[ORM\JoinColumn(name: 'book_id', nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return ClassifiedAds::class;
	}

	public function getClassName()
	{
		return 'ClassifiedAdsVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity($entity) {
        $this->entity = $entity;
    }
}