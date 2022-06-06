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
	*/
    private $news;

	public function getMainEntityClassName()
	{
		return News::class;
	}
	
	public function getClassName()
	{
		return 'NewsVote';
	}

    /**
     * Set news
     *
     * @param  App\Entity\News  $news
     */
    public function setNews(News $news)
    {
        $this->news = $news;
    }

    /**
     * Get news
     *
     * @return App\Entity\News 
     */
    public function getNews()
    {
        return $this->news;
    }
}