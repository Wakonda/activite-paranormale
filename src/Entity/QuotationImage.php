<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'quotationimage')]
#[ORM\Entity(repositoryClass: 'App\Repository\QuotationImageRepository')]
class QuotationImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

	#[ORM\ManyToOne(targetEntity: Quotation::class, inversedBy: 'images')]
    protected $quotation;

	#[ORM\Column(type: 'text', length: 255, nullable: true)]
    protected $image;

	public function __construct(String $image = null)
	{
		$this->image = $image;
	}

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getQuotation()
    {
        return $this->quotation;
    }

    public function setQuotation(Quotation $quotation = null)
    {
        $this->quotation = $quotation;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }
}