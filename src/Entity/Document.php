<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Language;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Document
 *
 * @ORM\Table(name="document")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 */
class Document extends MappedSuperclassBase
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
	 * @Assert\File(maxSize="6000000")
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pdfDoc;

	/**
     * @ORM\ManyToOne(targetEntity="App\Entity\DocumentFamily")
     */
    private $documentFamily;

    /**
     * @var string $releaseDateOfDocument
     *
     * @ORM\Column(name="releaseDateOfDocument", type="string", length=12, nullable=true)
     */
    private $releaseDateOfDocument;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Biography", inversedBy="documents", cascade={"persist"})
     * @ORM\JoinTable(name="document_biography",
     *      joinColumns={@ORM\JoinColumn(name="document_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="biography_id", referencedColumnName="id", onDelete="cascade")}     
     *      )
     */
	protected $authorDocumentBiographies;

	public function __construct()
	{
		parent::__construct();
		$this->authorDocumentBiographies = new \Doctrine\Common\Collections\ArrayCollection();
	}

	public function getAssetImagePath()
	{
		return "extended/photo/document/";
	}

    public function getReleaseDateOfDocumentText(): string
    {
		if(empty($this->releaseDateOfDocument))
			return "Unknown";

        return $this->releaseDateOfDocument;
    }

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
     * Set pdfDoc
     *
     * @param string $pdfDoc
     */
    public function setPdfDoc($pdfDoc)
    {
        $this->pdfDoc = $pdfDoc;
    }

    /**
     * Get pdfDoc
     *
     * @return string 
     */
    public function getPdfDoc()
    {
        return $this->pdfDoc;
    }

	public function getFullPicturePath() {
        return null === $this->pdfDoc ? null : $this->getUploadRootDir(). $this->pdfDoc;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir();
    }

    public function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return __DIR__ . '/../../public/'.$this->getAssetImagePath();
    }

	public function getShowRoute()
	{
		return "DocumentBundle_ReadDocument";
	}

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadPdfDoc() {
        // the file property can be empty if the field is not required
        if (null === $this->pdfDoc) {
            return;
        }

		if(is_object($this->pdfDoc))
		{
			$NameFile = basename($this->pdfDoc->getClientOriginalName());
			$reverseNF = strrev($NameFile);
			$explodeNF = explode(".", $reverseNF, 2);
			$NNFile = strrev($explodeNF[1]);
			$ExtFile = strrev($explodeNF[0]);
			$NewNameFile = uniqid().'-'.$NNFile.".".$ExtFile;
			if(!$this->id){
				$this->pdfDoc->move($this->getTmpUploadRootDir(), $NewNameFile);
			}else{
				if (is_object($this->pdfDoc))
					$this->pdfDoc->move($this->getUploadRootDir(), $NewNameFile);
			}
			if (is_object($this->pdfDoc))
				$this->setPdfDoc($NewNameFile);
		} elseif(filter_var($this->pdfDoc, FILTER_VALIDATE_URL)) {
			$parser = new \App\Service\APParseHTML();
			$html = $parser->getContentURL($this->pdfDoc);
			$pi = pathinfo($this->pdfDoc);
			$filename = $pi["filename"].".".$pi["extension"];
			$filename = uniqid()."_".$filename;

			file_put_contents($this->getTmpUploadRootDir().$filename, $html);
			$this->setPdfDoc($filename);
		}
    }	

    public function getDocumentFamily()
    {
        return $this->documentFamily;
    }

    public function setDocumentFamily(DocumentFamily $documentFamily)
    {
        $this->documentFamily = $documentFamily;
    }

  /**
    * Add authorDocumentBiographies
    *
    * @param App\Entity\Biography $authorDocumentBiographies
    */
	public function addAuthorDocumentBiography(Biography $biography)
	{
		$this->authorDocumentBiographies[] = $biography;
	}

    /**
     * Set authorDocumentBiographies
     *
     * @param string $authorDocumentBiographies
     */
    public function setAuthorDocumentBiographies($authorDocumentBiographies)
    {
        $this->authorDocumentBiographies = $authorDocumentBiographies;
    }

  /**
    * Remove authorDocumentBiographies
    *
    * @param App\Entity\Biography $authorDocumentBiographies
    */
	public function removeAuthorDocumentBiography(Biography $biography)
	{
		$this->authorDocumentBiographies->removeElement($biography);
	}

  /**
    * Get authorDocumentBiographies
    *
    * @return Doctrine\Common\Collections\Collection
    */
	public function getAuthorDocumentBiographies()
	{
		return $this->authorDocumentBiographies;
	}

    /**
     * Set releaseDateOfDocument
     *
     * @param string $releaseDateOfDocument
     */
    public function setReleaseDateOfDocument($releaseDateOfDocument)
    {
        $this->releaseDateOfDocument = $releaseDateOfDocument;
    }

    /**
     * Get releaseDateOfDocument
     *
     * @return string 
     */
    public function getReleaseDateOfDocument()
    {
        return $this->releaseDateOfDocument;
    }
}