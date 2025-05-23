<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Service\APPurifierHTML;

use App\Entity\EntityLinkBiography;

#[ORM\Table(name: 'book_edition_biography')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'App\Repository\BookEditionBiographyRepository')]
class BookEditionBiography extends EntityLinkBiography
{
	const PREFACE_OCCUPATION = "preface";
	const TRANSLATOR_OCCUPATION = "translator";
	const AUTHOR_OCCUPATION = "author";

	#[ORM\ManyToOne(targetEntity: 'BookEdition', inversedBy: 'biographies')]
	#[ORM\JoinColumn(name: 'book_edition_id', referencedColumnName: 'id')]
	private $bookEdition;

	#[ORM\ManyToOne(targetEntity: 'Book', inversedBy: 'biographies')]
	#[ORM\JoinColumn(name: 'book_id', referencedColumnName: 'id')]
	private $book;

	public static function getOccupations(): Array {
		return [
			self::PREFACE_OCCUPATION,
			self::TRANSLATOR_OCCUPATION,
			self::AUTHOR_OCCUPATION
		];
	}

    public function setBookEdition($bookEdition)
    {
        $this->bookEdition = $bookEdition;
    }

    public function getBookEdition()
    {
        return $this->bookEdition;
    }

    public function setBook($book)
    {
        $this->book = $book;
    }

    public function getBook()
    {
        return $this->book;
    }
}