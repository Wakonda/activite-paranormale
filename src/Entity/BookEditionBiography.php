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

	public static function getOccupations(): Array {
		return [
			self::PREFACE_OCCUPATION,
			self::TRANSLATOR_OCCUPATION
		];
	}

    /**
     * Set bookEdition
     *
     * @param string $bookEdition
     */
    public function setBookEdition($bookEdition)
    {
        $this->bookEdition = $bookEdition;
    }

    /**
     * Get bookEdition
     *
     * @return string 
     */
    public function getBookEdition()
    {
        return $this->bookEdition;
    }
}