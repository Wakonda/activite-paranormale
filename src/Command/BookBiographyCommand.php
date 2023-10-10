<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\BookEditionBiography;
use App\Entity\Book;
use App\Entity\Biography;

#[AsCommand(
   name: 'app:migrate-book-biography'
)]
class BookBiographyCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start BookBiography migration");

		$conn = $this->em->getConnection();

		$sql = "SELECT * FROM book_biography";
		$datas = $conn->fetchAllAssociative($sql);

		$mbArray = new \Doctrine\Common\Collections\ArrayCollection();

		foreach($datas as $data)
		{
			$book = $this->em->getRepository(Book::class)->find($data["book_id"]);
			$biography = $this->em->getRepository(Biography::class)->find($data["biography_id"]);
			$entity = $this->em->getRepository(BookEditionBiography::class)->findOneBy(["biography" => $biography, "book" => $book]);
			
			if(empty($entity))
				$entity = new BookEditionBiography();

			$entity->setOccupation("author");
			$entity->setBook($book);
			$entity->setBiography($biography);

			$mbArray->add($entity);

			$book->setBiographies($mbArray);
			
			$this->em->persist($book);
			$this->em->persist($entity);
		}

		$this->em->flush();

		echo "End book biography";

        return 0;
    }
}