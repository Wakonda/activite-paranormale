<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BookCommentRepository")
 * @ORM\Table(name="bookcomment")
 */
class BookComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Book")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return Book::class;
	}
	
	public function getClassName()
	{
		return 'BookComment';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Book $entity
     */
    public function setEntity(Book $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Book
     */
    public function getEntity()
    {
        return $this->entity;
    }
}