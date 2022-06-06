<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GrimoireVoteRepository")
 * @ORM\Table(name="grimoirevote")
 */
class GrimoireVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Grimoire")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $grimoire;

	public function getMainEntityClassName()
	{
		return Grimoire::class;
	}
	
	public function getClassName()
	{
		return 'GrimoireVote';
	}

    /**
     * Set grimoire
     *
     * @param App\Entity\Grimoire $grimoire
     */
    public function setGrimoire(Grimoire $grimoire)
    {
        $this->grimoire = $grimoire;
    }

    /**
     * Get grimoire
     *
     * @return App\Entity\Grimoire 
     */
    public function getGrimoire()
    {
        return $this->grimoire;
    }
}