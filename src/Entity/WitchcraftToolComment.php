<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WitchcraftToolCommentRepository")
 * @ORM\Table(name="witchcrafttoolcomment")
 */
class WitchcraftToolComment extends Comment
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\WitchcraftTool")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getEntityLinked()
	{
		return $this->entity;
	}
	
	public function getMainEntityClassName()
	{
		return WitchcraftTool::class;
	}
	
	public function getClassName()
	{
		return 'WitchcraftToolComment';
	}

    /**
     * Set entity
     *
     * @param App\Entity\WitchcraftTool $entity
     */
    public function setEntity(WitchcraftTool $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\WitchcraftTool 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}