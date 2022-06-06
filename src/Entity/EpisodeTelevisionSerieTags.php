<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Movies\EpisodeTelevisionSerie;

/**
 * @ORM\Entity()
 * @ORM\Table(name="episodetelevisionserietags")
 */
class EpisodeTelevisionSerieTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Movies\EpisodeTelevisionSerie")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return EpisodeTelevisionSerie::class;
	}
	
	public function getClassName()
	{
		return 'EpisodeTelevisionSerieTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Movies\EpisodeTelevisionSerie $entity
     */
    public function setEntity(EpisodeTelevisionSerie $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Movies\EpisodeTelevisionSerie
     */
    public function getEntity()
    {
        return $this->entity;
    }
}