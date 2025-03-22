<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'music')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\MusicRepository')]
class Music
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

	#[Assert\File(maxSize: '5M')]
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $musicPieceFile;

	#[ORM\Column(name: 'musicPiece', type: 'string', length: 255)]
    private $musicPiece;

	#[ORM\Column(type: 'text', nullable: true)]
	protected $text;

	#[ORM\Column(name: 'length', type: 'string', length: 255, nullable: true)]
    private $length;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Album')]
    private $album;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\Artist')]
    private $artist;

	#[ORM\Column(name: 'embeddedCode', type: 'text', nullable: true)]
	private $embeddedCode;

	#[ORM\Column(name: 'source', type: 'text', nullable: true)]
    private $source;

	#[ORM\Column(name: 'wikidata', type: 'string', length: 15, nullable: true)]
	private $wikidata;

	#[ORM\Column(name: 'identifiers', type: 'text', nullable: true)]
    private $identifiers;

	#[ORM\OneToMany(targetEntity: "App\Entity\MusicBiography", mappedBy: "music", cascade: ["persist"])]
	#[ORM\JoinTable(
		name: "music_biography",
		joinColumns: [
			new ORM\JoinColumn(name: "music_id", referencedColumnName: "id", onDelete: "cascade")
		],
		inverseJoinColumns: [
			new ORM\JoinColumn(name: "biography_id", referencedColumnName: "id", onDelete: "cascade")
		]
	)]
	private $musicBiographies;

	#[ORM\ManyToOne(targetEntity: 'App\Entity\EventMessage')]
    private $event;

	#[ORM\Column(name: 'slug', type: 'string', length: 255, nullable: true)]
    protected $slug;
	
	public function getTitle(): string {
		$album = !empty($this->album) ? $this->album->getTitle() : null;
		$artist = !empty($this->artist) ? $this->artist->getTitle() : (!empty($this->album) ? $this->album->getArtist()->getTitle() : null);

		return $artist." - ".$this->musicPiece.(!empty($album) ? " (".$album.")" : "");
	}

	public function getShowRoute() {
		return "Music_Music";
	}

	public function getLanguage() {
		if(!empty($e = $this->album))
			return $e->getLanguage();
		elseif(!empty($e = $this->artist))
			return $e->getLanguage();
			
		return null;
	}

	public function getRealClass()
	{
		$classname = get_class($this);

		if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
			$classname = $matches[1];
		}

		return $classname;
	}

	public function getEntityName()
	{
		return get_called_class();
	}

    public function getId()
    {
        return $this->id;
    }

    public function setMusicPiece($musicPiece)
    {
		if(!empty($musicPiece))
			$this->musicPiece = htmlspecialchars($musicPiece, ENT_NOQUOTES, 'UTF-8');

		$this->setSlug();
    }

    public function setSlug()
    {
		if(empty($this->slug)) {
			$generator = new \Ausi\SlugGenerator\SlugGenerator;
			$this->slug = $generator->generate($this->musicPiece);
		}
    }

    public function getSlug()
    {
        return $this->slug;
    }

	public function getUrlSlug()
	{
		return !empty($this->slug) ? $this->slug : $this->musicPiece;
	}

    public function getMusicPieceFile()
    {
        return $this->musicPieceFile;
    }

    public function setMusicPieceFile($musicPieceFile)
    {
        $this->musicPieceFile = $musicPieceFile;
    }

	public function getFullPicturePath() {
        return null === $this->musicPieceFile ? null : $this->getUploadRootDir(). $this->musicPieceFile;
    }

    public function getUploadRootDir() {
        return $this->getTmpUploadRootDir();
    }

	public function getAssetMusicPath()
	{
		return "extended/flash/Music/MP3/";
	}

    public function getTmpUploadRootDir() {
        return __DIR__ . '/../../public/'.$this->getAssetMusicPath();
    }

	#[ORM\PrePersist]
	#[ORM\PreUpdate]
    public function uploadMpMusicPiece() {
        if (null === $this->musicPieceFile) {
            return;
        }

		if(is_object($this->musicPieceFile))
		{
			$NameFile = basename($this->musicPieceFile->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->musicPieceFile->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->musicPieceFile))
					$this->musicPieceFile->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->musicPieceFile))
				$this->setMusicPieceFile($NewNameFile);
		} elseif(filter_var($this->musicPieceFile, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->musicPieceFile);
			$pi = pathinfo($this->musicPieceFile);
			$extension = $res = pathinfo(parse_url($this->musicPieceFile, PHP_URL_PATH), PATHINFO_EXTENSION);
			$filename = preg_replace('#\W#', '', $pi["filename"]).".".$extension;
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setMusicPieceFile($filename);
		}
    }

    public function getMusicPiece()
    {
        return $this->musicPiece;
    }

    public function setLength($length)
    {
        $this->length = $length;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getAlbum()
    {
        return $this->album;
    }

    public function setAlbum(Album $album)
    {
        $this->album = $album;
    }

    public function getArtist()
    {
        return $this->artist;
    }

    public function setArtist($artist)
    {
        $this->artist = $artist;
    }

    public function setEmbeddedCode($embeddedCode)
    {
        $this->embeddedCode = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace( array('<html>', '</html>', '<body>', '</body>'), array('', '', '', ''), $embeddedCode));
    }

    public function getEmbeddedCode()
    {
        return $this->embeddedCode;
    }

    public function setText($text)
    {
		$this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setWikidata($wikidata)
    {
        $this->wikidata = $wikidata;
    }

    public function getWikidata()
    {
        return $this->wikidata;
    }

    public function setIdentifiers($identifiers)
    {
        $this->identifiers = $identifiers;
    }

    public function getIdentifiers()
    {
        return $this->identifiers;
    }

	public function getMusicBiographies()
	{
		return $this->musicBiographies;
	}

	public function setMusicBiographies($musicBiographies)
	{
		$this->musicBiographies = $musicBiographies;
	}

	public function getEvent()
	{
		return $this->event;
	}

	public function setEvent($event)
	{
		$this->event = $event;
	}
}