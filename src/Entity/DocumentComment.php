<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: 'App\Repository\DocumentCommentRepository')]
#[ORM\Table(name: 'documentcomment')]
class DocumentComment extends Comment
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Document')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Document::class;
	}
	
	public function getClassName()
	{
		return 'DocumentComment';
	}

    public function setEntity(Document $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}