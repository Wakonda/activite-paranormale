<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TestimonyVoteRepository")
 * @ORM\Table(name="testimonyvote")
 */
class TestimonyVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Testimony")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $testimony;

	public function getMainEntityClassName()
	{
		return Testimony::class;
	}
	
	public function getClassName()
	{
		return 'TestimonyVote';
	}

    /**
     * Set testimony
     *
     * @param App\Entity\Testimony $testimony
     */
    public function setTestimony(Testimony $testimony)
    {
        $this->testimony = $testimony;
    }

    /**
     * Get testimony
     *
     * @return App\Entity\Testimony 
     */
    public function getTestimony()
    {
        return $this->testimony;
    }
}