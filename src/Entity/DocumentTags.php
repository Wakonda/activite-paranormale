<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'documenttags')]
class DocumentTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Document')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return Document::class;
	}
	
	public function getClassName()
	{
		return 'DocumentTags';
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