<?php

namespace App\Entity\Stores;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use App\Filter\OrSearchFilter;

use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
    normalizationContext: ['groups' => ['api_book_read', 'api_read']],
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['book.book.theme.title' => 'exact', 'book.book.authors.title' => 'exact', 'book.book.language.abbreviation' => 'exact'])]
#[ApiFilter(OrSearchFilter::class, properties: ['title', 'text', 'book.book.authors.title', 'book.book.theme.title'])]
class BookStore extends Store {
	#[ORM\ManyToOne(targetEntity: 'App\Entity\BookEdition')]
	#[ORM\Groups('api_book_read')]
    protected $book;

	public function getBook()
    {
        return $this->book;
    }

    public function setBook($book)
    {
        $this->book = $book;
    }
	
	public function __construct()
	{
		$this->setCategory(Store::BOOK_CATEGORY);
	}
	
	public function getLinkedEntityName() {
		return $this->book->getBook()->getRealClass();
	}
}