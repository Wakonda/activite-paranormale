<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="newscomment")
 * @ORM\Entity(repositoryClass="App\Repository\NewsCommentRepository")
 */
class NewsComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\News")
	*/
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return News::class;
	}
	
	public function getClassName()
	{
		return 'NewsComment';
	}

    /**
     * Set entity
     *
     * @param  App\Entity\News  $entity
     */
    public function setEntity(News $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\News 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}