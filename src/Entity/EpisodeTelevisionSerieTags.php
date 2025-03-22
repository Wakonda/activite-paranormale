<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Movies\EpisodeTelevisionSerie;

#[ORM\Entity]
#[ORM\Table(name: 'episodetelevisionserietags')]
class EpisodeTelevisionSerieTags extends Tags
{
	#[ORM\ManyToOne(targetEntity: 'App\Entity\Movies\EpisodeTelevisionSerie')]
	#[ORM\JoinColumn(nullable: false)]
    private $entity;

	public function getMainEntityClassName()
	{
		return EpisodeTelevisionSerie::class;
	}
	
	public function getClassName()
	{
		return 'EpisodeTelevisionSerieTags';
	}

    public function setEntity(EpisodeTelevisionSerie $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}