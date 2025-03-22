<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\VideoCommentRepository')]
#[ORM\Table(name: 'videocomment')]
class VideoComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Video')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Video::class;
	}
	
	public function getClassName()
	{
		return 'VideoComment';
	}

    public function setEntity(Video $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}