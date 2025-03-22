<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Movies\TelevisionSerie;

#[ORM\Entity]
#[ORM\Table(name: 'televisionserietags')]
class TelevisionSerieTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Movies\TelevisionSerie')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return TelevisionSerie::class;
	}

	public function getClassName()
	{
		return 'TelevisionSerieTags';
	}

    public function setEntity(TelevisionSerie $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}