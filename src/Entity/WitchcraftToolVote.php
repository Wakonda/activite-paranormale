<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WitchcraftToolVoteRepository")
 * @ORM\Table(name="witchcrafttoolvote")
 */
class WitchcraftToolVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\WitchcraftTool")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $witchcraftTool;

	public function getMainEntityClassName()
	{
		return WitchcraftTool::class;
	}
	
	public function getClassName()
	{
		return 'WitchcraftToolVote';
	}

    /**
     * Set witchcraftTool
     *
     * @param App\Entity\WitchcraftTool $witchcraftTool
     */
    public function setWitchcraftTool(WitchcraftTool $witchcraftTool)
    {
        $this->witchcraftTool = $witchcraftTool;
    }

    /**
     * Get witchcraftTool
     *
     * @return App\Entity\WitchcraftTool
     */
    public function getWitchcraftTool()
    {
        return $this->witchcraftTool;
    }
}