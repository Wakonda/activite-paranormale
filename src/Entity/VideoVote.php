<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VideoVoteRepository")
 * @ORM\Table(name="videovote")
 */
class VideoVote extends Vote
{
   /**
    * @ORM\ManyToOne(targetEntity="App\Entity\Video")
	* @ORM\JoinColumn(nullable=false)
	*/
    private $video;

	public function getMainEntityClassName()
	{
		return Video::class;
	}
	
	public function getClassName()
	{
		return 'VideoVote';
	}

    /**
     * Set video
     *
     * @param App\Entity\Video $video
     */
    public function setVideo(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Get video
     *
     * @return App\Entity\Video 
     */
    public function getVideo()
    {
        return $this->video;
    }
}