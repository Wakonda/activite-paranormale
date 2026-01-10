<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Service\APPurifierHTML;

#[ORM\Table(name: 'publisher')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\PublisherRepository')]
class Publisher
{
	use \App\Entity\GenericEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $photo;

	#[ORM\Column(type: 'string', length: 255)]
	#[Groups('api_read')]
    private $title;	

	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $website;

	#[ORM\OneToOne(targetEntity: 'FileManagement', cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(name: 'illustration_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private $illustration;

	#[ORM\Column(name: 'text', type: 'text', nullable: true)]
	#[Groups('api_read')]
    private $text;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
	private $internationalName;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\Column(name: 'socialNetwork', type: 'text', nullable: true)]
    private $socialNetwork;

    public function getId()
    {
        return $this->id;
    }

    public function setPhoto($photo)
    {
        $this->photo = $photo;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

	public function getFullPicturePath() {
        return null === $this->photo ? null : realpath($this->getUploadRootDir(). $this->photo);
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/book/publisher/";
	}

    public function getTmpUploadRootDir() {
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

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setWebsite($website)
    {
        $this->website = $website;
    }

    public function getWebsite()
    {
        return $this->website;
    }

    public function setIllustration($illustration)
    {
        $this->illustration = $illustration;
    }

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

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setSocialNetwork($socialNetwork)
    {
        $this->socialNetwork = $socialNetwork;
    }

    public function getSocialNetwork()
    {
        return $this->socialNetwork;
    }

	public function getSocialNetworkUsername(string $socialNetwork) {
		if(empty($this->socialNetwork))
			return null;
	
		$res = "";
		foreach(json_decode($this->socialNetwork, true) as $sn) {
			if(!empty($sn["url"]) and strtolower($sn["socialNetwork"]) == strtolower($socialNetwork))
				$res = "@".ltrim(parse_url($sn["url"], PHP_URL_PATH), "@/");
		}

		return $res;
	}
}