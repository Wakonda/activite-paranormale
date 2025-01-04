<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Service\APPurifierHTML;

/**
 * App\Entity\Publisher
 *
 * @ORM\Table(name="publisher")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\PublisherRepository")
 */
class Publisher
{
	use \App\Entity\GenericEntityTrait;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
	 * Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;
	
    /**
     * @var string $title
     *
     * @ORM\Column(type="string", length=255)
	 * @Groups("api_read")
     */
    private $title;	

    /**
     * @var string $title
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $website;

    /**
     * @ORM\OneToOne(targetEntity="FileManagement", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="illustration_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $illustration;

    /**
     * @var text $text
     *
     * @ORM\Column(name="text", type="text", nullable=true)
	 * @Groups("api_read")
     */
    private $text;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\Language")
     */
    private $language;

	/**
	 * @var string $internationalName
	 *
	 * @ORM\Column(name="internationalName", type="string", length=255)
	 */
	private $internationalName;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set photo
     *
     * @param string $photo
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    /**
     * Get photo
     *
     * @return string 
     */
    public function getPhoto()
    {
        return $this->photo;
    }

	public function getFullPicturePath() {
        return null === $this->photo ? null : realpath($this->getUploadRootDir(). $this->photo);
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/book/publisher/";
	}

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	public function __clone()
	{
		if(!empty($this->illustration))
			$this->illustration = clone $this->illustration;
	}

	protected function purifier($text)
	{
		$purifier = new APPurifierHTML();
		return $purifier->purifier($text);
	}

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set website
     *
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * Get website
     *
     * @return string 
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set illustration
     *
     * @param string $illustration
     */
    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

    /**
     * Get illustration
     *
     * @return string 
     */
    public function getIllustration()
    {
        return $this->illustration;
    }

    public function setText($text)
    {
        $this->text = $this->purifier($text);
    }

    public function getText()
    {
        return $this->text;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }
}