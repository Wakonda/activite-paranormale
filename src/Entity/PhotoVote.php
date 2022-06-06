<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PhotoVoteRepository")
 * @ORM\Table(name="photovote")
 */
class PhotoVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Photo")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $photo;

	public function getMainEntityClassName()
	{
		return Photo::class;
	}
	
	public function getClassName()
	{
		return 'PhotoVote';
	}

    /**
     * Set photo
     *
     * @param App\Entity\Photo $photo
     */
    public function setPhoto(Photo $photo)
    {
        $this->photo = $photo;
    }

    /**
     * Get photo
     *
     * @return App\Entity\Photo
     */
    public function getPhoto()
    {
        return $this->photo;
    }
}