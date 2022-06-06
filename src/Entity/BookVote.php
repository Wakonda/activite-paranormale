<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BookVoteRepository")
 * @ORM\Table(name="bookvote")
 */
class BookVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Book")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $book;

	public function getMainEntityClassName()
	{
		return Book::class;
	}
	
	public function getClassName()
	{
		return 'BookVote';
	}

    /**
     * Set book
     *
     * @param App\Entity\Book $book
     */
    public function setBook(Book $book)
    {
        $this->book = $book;
    }

    /**
     * Get book
     *
     * @return App\Entity\Book
     */
    public function getBook()
    {
        return $this->book;
    }
}