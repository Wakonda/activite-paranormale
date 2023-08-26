<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\FileManagement;
use App\Entity\Biography;
use App\Entity\Book;
use App\Entity\BookEdition;
use App\Entity\Movies\Movie;
use App\Entity\Movies\MovieBiography;
use App\Entity\Stores\MovieStore;
use App\Entity\Stores\Store;
use App\Entity\Stores\BookStore;
use App\Entity\Stores\AlbumStore;
use App\Entity\Album;

#[AsCommand(
   name: 'app:biography-role'
)]
class BiographyRoleCommand extends Command
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
		$conn = $this->em->getConnection();
		
		$conn->exec("UPDATE grimoire set source = CONCAT('[{\"author\":\"\",\"title\":\"\",\"url\":\"', source, '\",\"type\":\"url\"}]') WHERE source REGEXP \"^(https?://|www\\.)[\.A-Za-z0-9\-]+\\.[a-zA-Z]{2,4}\"");
		die("ok");
		
		$conn->exec("UPDATE entity_link_biography SET occupation = 'director' WHERE occupation = 'filmmaker' AND discr = 'movie_biography'");
		$conn->exec("UPDATE entity_link_biography SET occupation = 'screenwriter' WHERE occupation = 'writer' AND discr = 'movie_biography'");
		$conn->exec("UPDATE grimoire SET archive = false WHERE year(publicationDate) >= 2020;");
		// die(get_class($conn));
		
		/*$stmt = $conn->prepare("SELECT movie_id, biography_id, role, occupation FROM movie_biography");
        $stmt->execute();
        
		$datas = $stmt->fetchAll();
		
		// file_put_contents("movie_biography.txt", json_encode($datas));
		// die(var_dump(json_encode($datas)));
		$conn->exec("DELETE FROM movie_biography");
		
		$command = $this->getApplication()->find('doctrine:schema:update');
		
        $arguments = [
            '--force'  => true,
        ];

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);
		
		foreach($datas as $data)
		{
			$mb = new MovieBiography();
			
			$this->em->persist($mb);
				
			$biography = $this->em->getRepository(Biography::class)->find($data["biography_id"]);
			$movie = $this->em->getRepository(Movie::class)->find($data["movie_id"]);
// die(var_dump($data, $data["role"], $data["occupation"]));
			$mb->setRole($data["role"]);
			$mb->setOccupation($data["occupation"]);
			$mb->setMovie($movie);
			$mb->setBiography($biography);
			
			if(empty($movie->getMovieBiographies()))
				$mbArray = new \Doctrine\Common\Collections\ArrayCollection();
			else
				$mbArray = $movie->getMovieBiographies();

			$mbArray->add($mb);
			
			$movie->setMovieBiographies($mbArray);
			$this->em->persist($mb);
			$this->em->persist($movie);			
			// var_dump($mb->getRole());die;
		}
		
		$this->em->flush();die("ok");*/
		// var_dump($returnCode);die;
		
		/*$output->writeln("Start Biography role");

		$conn = $this->em->getConnection();
		
		// $conn->exec("INSERT INTO entity_link_biography (biography_id, role, occupation) SELECT biography_id, role, occupation FROM movie_biography;");
		// $conn->exec("INSERT INTO entity_link_biography (biography_id, role, occupation) SELECT biography_id, role, occupation FROM artist_biography;");
		// $conn->exec("INSERT INTO entity_link_biography (biography_id, role, occupation) SELECT biography_id, role, occupation FROM televisionserie_biography;");

		// Migrate Book / BookEdition
		$books = $this->em->getRepository(Book::class)->findAll();
		
		foreach($books as $book) {
			$bookEdition = new BookEdition();

			if(!empty($book->getPhoto())) {
				$illustration = new FileManagement();
				$illustration->setTitleFile($book->getPhoto());
				$illustration->setRealNameFile($book->getPhoto());
				$illustration->setExtensionFile(pathinfo($book->getPhoto(), PATHINFO_EXTENSION));
				$illustration->setKindFile("file");
				$this->em->persist($illustration);
				
				$bookEdition->setIllustration($illustration);
			}

			$bookEdition->setIsbn10($book->getIsbn10());
			$bookEdition->setIsbn13($book->getIsbn13());
			$bookEdition->setBackCover($book->getText());
			$bookEdition->setNumberPage($book->getNumberPage());
			$bookEdition->setPublisher($book->getPublisher());
			$bookEdition->setFormat("paperback");
			$bookEdition->setPublicationDate($book->getPublicationDate());
			
			$this->em->persist($bookEdition);
			
			$book->addBookEdition($bookEdition);
			$bookEdition->setBook($book);
			
			$this->em->persist($book);
		}
		$this->em->flush();
		die("ok");*/
		// Migrate store
		// --> Movie
		/*$movies = $this->em->getRepository(Movie::class)->findAll();
		
		foreach($movies as $movie) {
			if(empty($movie->getStore()))
				continue;
			
			$storeArray = json_decode($movie->getStore(), true);
			$store = new MovieStore();
			// die(var_dump($storeArray));
			$store->setTitle($movie->getTitle());
			$store->setLanguage($movie->getLanguage());
			$store->setImageEmbeddedCode($storeArray[0]["embeddedCode"]);
			$store->setAmazonCode($storeArray[0]["asin"]);
			$store->setPlatform(Store::AMAZON_PLATFORM);
			$store->setCategory(Store::MOVIE_CATEGORY);
			$store->setMovie($movie);
			
			if(isset($storeArray[0]["format"]) and !empty($storeArray[0]["format"]))
				$store->setCharacteristic(json_encode(["format" => $storeArray[0]["format"]]));
			
			$this->em->persist($store);
		}
		// die("ok");
		$this->em->flush();
		die("ok");*/
		// --> Book
		$books = $this->em->getRepository(Book::class)->findAll();
		
		foreach($books as $book) {
			if(empty($book->getStore()))
				continue;
			
			$store = new BookStore();
			
			$store->setTitle($book->getTitle());
			$store->setLanguage($book->getLanguage());
			$store->setImageEmbeddedCode($book->getStore());
			$store->setAmazonCode($book->getAmazonCode());
			$store->setPlatform(Store::AMAZON_PLATFORM);
			$store->setCategory(Store::BOOK_CATEGORY);
			$store->setBook($book->getBookEditions()[0]);
			
			$this->em->persist($store);
		}
		
		$this->em->flush();
		die("lll");
		// --> Album
		$albums = $this->em->getRepository(Album::class)->findAll();
		
		foreach($albums as $album) {
			if(empty($album->getStore()))
				continue;
			
			$storeArray = json_decode($album->getStore(), true);
			$store = new AlbumStore();
			
			$store->setTitle($album->getTitle());
			$store->setLanguage($album->getLanguage());
			$store->setImageEmbeddedCode(!empty($ec = $storeArray["embeddedCode"]) ? $ec : "-");
			$store->setAmazonCode($storeArray["asin"]);
			$store->setPlatform(Store::AMAZON_PLATFORM);
			$store->setCategory(Store::ALBUM_CATEGORY);
			$store->setAlbum($album);
			
			if(isset($storeArray["format"]) and !empty($storeArray["format"]))
				$store->setCharacteristic(["format" => $storeArray["format"]]);
			
			$this->em->persist($store);
		}
		
		$this->em->flush();


        return 0;
    }
}