<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Movies\TelevisionSerie;

/**
 * @ORM\Table(name="televisionserievote")
 * @ORM\Entity(repositoryClass="App\Repository\TelevisionSerieVoteRepository")
 */
class TelevisionSerieVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Movies\TelevisionSerie")
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return TelevisionSerie::class;
	}
	
	public function getClassName()
	{
		return 'TelevisionSerieVote';
	}

    /**
     * Set TelevisionSerie
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