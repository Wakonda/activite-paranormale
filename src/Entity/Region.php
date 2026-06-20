<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ausi\SlugGenerator\SlugGenerator;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'region')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\RegionRepository')]
class Region
{
	const SUBDIVISION_FAMILY = "subdivision";
	const COUNTRY_FAMILY = "country";
	const AREA_FAMILY = "area";
	const CITY_FAMILY = "city";

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[ORM\Column(name: 'title', type: 'string', length: 255)]
    private $title;

	#[ORM\Column(name: 'internationalName', type: 'string', length: 255)]
    private $internationalName;

	#[Assert\File(maxSize: '6000000')]
	#[ORM\Column(name: 'flag', type: 'string', length: 255, nullable: true)]
    private $flag;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Language')]
    private $language;

	#[ORM\Column(name: 'family', type: 'string', length: 255)]
    private $family;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 20)]
	private $wikidata;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Region')]
	#[ORM\JoinColumn(name: 'higherLevel_id')]
    protected $higherLevel;

	#[ORM\Column(name: 'geoshape', type: 'text', nullable: true)]
	private $geoshape;

	#[ORM\Column(name: 'text', type: 'text', nullable: true)]
    private $text;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;
	
	public function isCityFamily(): bool {
		return $this->family == self::CITY_FAMILY;
	}
	
	public function __toString()
	{
		return $this->title;
	}

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
		$this->setInternationalName(null);
		$this->setSlug();
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setFlag($flag)
    {
        $this->flag = $flag;
    }

    public function getFlag()
    {
        return $this->flag;
    }
	
	public function getFullPicturePath() {
        return null === $this->flag ? null : $this->getUploadRootDir(). $this->flag;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetImagePath()
	{
		return "extended/photo/country/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadFlagCountry() {
        if (null === $this->flag) {
            return;
        }

		if(is_object($this->flag))
		{
			$NameFile = basename($this->flag->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				list($filename, $content) = \App\Service\APImgSize::convertToWebP($this->flag, $NewNameFile);

				file_put_contents($this->getTmpUploadRootDir().$filename, $content);
				$this->setFlag($filename);
			} else {
				if (is_object($this->flag)) {
					list($filename, $content) = \App\Service\APImgSize::convertToWebP($this->flag, $NewNameFile);

					file_put_contents($this->getTmpUploadRootDir().$filename, $content);
					$this->setFlag($filename);
				}
			}
			if (is_object($this->flag))
				$this->setFlag($NewNameFile);
		} elseif(filter_var($this->flag, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->flag);
			$pi = pathinfo($this->flag);
			$extension = $res = pathinfo(parse_url($this->flag, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			list($filename, $content) = \App\Service\APImgSize::convertToWebP($html, $filename);

			file_put_contents($this->getTmpUploadRootDir().$filename, $content);
			$this->setFlag($filename);
		}
    }

    public function setInternationalName($internationalName)
    {
        $this->internationalName = $internationalName;

		if(empty($this->internationalName)) {
			if(!empty($this->wikidata)) {
				$this->internationalName = $this->wikidata;
			} else {
				$generator = new SlugGenerator;
				$this->internationalName = $generator->generate($this->title);
			}
		}
    }

    public function getInternationalName()
    {
        return $this->internationalName;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }

    public function getFamily()
    {
        return $this->family;
    }

    public function setFamily($family)
    {
        $this->family = $family;
    }

    public function getHigherLevel()
    {
        return $this->higherLevel;
    }

    public function setHigherLevel($higherLevel)
    {
        $this->higherLevel = $higherLevel;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getGeoshape()
    {
        return $this->geoshape;
    }

    public function setGeoshape($geoshape)
    {
        $this->geoshape = $geoshape;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setSlug()
    {
		if(empty($this->slug)) {
			$generator = new \Ausi\SlugGenerator\SlugGenerator;
			$this->slug = $generator->generate($this->title);
		}
    }

    public function getSlug()
    {
        return $this->slug;
    }
}