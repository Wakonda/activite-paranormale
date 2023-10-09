<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Service\APPurifierHTML;

use App\Entity\EntityLinkBiography;

/**
 * App\Entity\BookEditionBiography
 *
 * @ORM\Table(name="book_edition_biography")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="App\Repository\BookEditionBiographyRepository")
 */
class BookEditionBiography extends EntityLinkBiography
{
	const PREFACE_OCCUPATION = "preface";
	const TRANSLATOR_OCCUPATION = "translator";

	/**
	 * @ORM\ManyToOne(targetEntity="BookEdition", inversedBy="biographies")
	 * @ORM\JoinColumn(name="book_edition_id", referencedColumnName="id")
	 */
	private $bookEdition;

	/**
	 * @ORM\ManyToOne(targetEntity="Book", inversedBy="biographies")
	 * @ORM\JoinColumn(name="book_id", referencedColumnName="id")
	 */
	private $book;

	public static function getOccupations(): Array {
		return [
			self::PREFACE_OCCUPATION,
			self::TRANSLATOR_OCCUPATION
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