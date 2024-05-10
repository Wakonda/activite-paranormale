<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Movies\TelevisionSerie;

/**
 * @ORM\Entity()
 * @ORM\Table(name="televisionserietags")
 */
class TelevisionSerieTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Movies\TelevisionSerie")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return TelevisionSerie::class;
	}

	public function getClassName()
	{
		return 'TelevisionSerieTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Movies\TelevisionSerie $entity
     */
    public function setEntity(TelevisionSerie $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Movies\TelevisionSerie
     */
    public function getEntity()
    {
        return $this->entity;
    }
}