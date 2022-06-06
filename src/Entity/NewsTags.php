<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="newstags")
 */
class NewsTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\News")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return News::class;
	}
	
	public function getClassName()
	{
		return 'NewsTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\News $entity
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