<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'testimonytags')]
class TestimonyTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Testimony')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return Testimony::class;
	}
	
	public function getClassName()
	{
		return 'TestimonyTags';
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