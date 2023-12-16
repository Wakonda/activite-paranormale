<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="newsvote")
 * @ORM\Entity(repositoryClass="App\Repository\NewsVoteRepository")
 */
class NewsVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\News")
	* @ORM\JoinColumn(name="news_id")
	*/
    private $entity;

	public function getMainEntityClassName()
	{
		return News::class;
	}
	
	public function getClassName()
	{
		return 'NewsVote';
	}

	public function getEntity() {
		return $this->entity;
	}

    public function setEntity(News $entity) {
        $this->entity = $entity;
    }
}