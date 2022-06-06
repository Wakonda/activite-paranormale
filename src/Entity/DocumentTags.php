<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="documenttags")
 */
class DocumentTags extends Tags
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Document")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return Document::class;
	}
	
	public function getClassName()
	{
		return 'DocumentTags';
	}

    /**
     * Set entity
     *
     * @param App\Entity\Document $entity
     */
    public function setEntity(Document $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get entity
     *
     * @return App\Entity\Document 
     */
    public function getEntity()
    {
        return $this->entity;
    }
}