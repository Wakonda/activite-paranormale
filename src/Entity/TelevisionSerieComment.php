<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Movies\TelevisionSerie;

/**
 * @ORM\Table(name="televisionseriecomment")
 * @ORM\Entity(repositoryClass="App\Repository\TelevisionSerieCommentRepository")
 */
class TelevisionSerieComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Movies\TelevisionSerie")
	*/
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return TelevisionSerie::class;
	}
	
	public function getClassName()
	{
		return 'TelevisionSerieComment';
	}

    /**
     * Set entity
     *
     * @param  App\Entity\Movies\TelevisionSerie  $entity
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