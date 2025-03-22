<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\TestimonyCommentRepository')]
#[ORM\Table(name: 'testimonycomment')]
class TestimonyComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Testimony')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Testimony::class;
	}
	
	public function getClassName()
	{
		return 'TestimonyComment';
	}

    public function setEntity(Testimony $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}