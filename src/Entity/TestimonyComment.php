<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestimonyCommentRepository")
 * @ORM\Table(name="testimonycomment")
 */
class TestimonyComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Testimony")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Testimony::class;
	}
	
	public function getClassName()
	{
		return 'TestimonyComment';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Testimony $entity
     */
    public function setEntity(Testimony $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Testimony 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}