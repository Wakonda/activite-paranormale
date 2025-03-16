<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Publisher;
use App\Entity\Language;
use App\Entity\FileManagement;

#[AsCommand(
   name: 'app:migrate-publisher'
)]
class MigratePublisherCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
		$output->writeln("Start publisher migration");

		$conn = $this->em->getConnection();

		$sql = "SELECT id, photo FROM publisher WHERE illustration_id IS NULL AND photo IS NOT NULL";
		$datas = $conn->fetchAllAssociative($sql);

		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchOne("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE publisher SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		echo "End publisher photo";
		
		$sql = "select 
				p.id,
				p.title,
				(select group_concat(DISTINCT l.abbreviation ORDER BY l.abbreviation ASC SEPARATOR ',')
				from book_edition be
				join book b on b.id = be.book_id
				join language l on l.id = b.language_id
				where be.publisher_id = p.id) AS languages
				from publisher p";
		
		$datas = $conn->fetchAllAssociative($sql);
		
		$languageEntities = [
			"fr" => $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "fr"]),
			"en" => $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]),
			"es" => $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "es"]),
			"it" => $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "it"])
		];

		foreach($datas as $data) {
			$languages = $data["languages"];
			
			if(!empty($languages)) {
				$languageArray = explode(",", $languages);

				if(count($languageArray) == 1) {
					$publisher = $this->em->getRepository(Publisher::class)->find($data["id"]);
					$publisher->setLanguage($languageEntities[$languageArray[0]]);
					$this->em->persist($publisher);
				} else {
					
					foreach($languageArray as $key => $la) {
						if($key === array_key_first($datas)) {
							$publisher = $this->em->getRepository(Publisher::class)->find($data["id"]);
							$publisher->setLanguage($languageEntities[$la]);
							$this->em->persist($publisher);
						
							continue;
						}

						$newPublisher = new Publisher();
						$newPublisher->setTitle($publisher->getTitle());
						$newPublisher->setWebsite($publisher->getWebsite());
						$newPublisher->setLanguage($languageEntities[$la]);
						$this->em->persist($newPublisher);
						
						if(!empty($ci = $publisher->getIllustration())) {
							$illustration = new FileManagement();
							$illustration->setTitleFile($ci->getTitleFile());
							$illustration->setRealNameFile($ci->getRealNameFile());
							$illustration->setCaption($ci->getCaption());
							$illustration->setLicense($ci->getLicense());
							$illustration->setAuthor($ci->getAuthor());
							$illustration->setUrlSource($ci->getUrlSource());
							$illustration->setExtensionFile($ci->getExtensionFile());

							$newPublisher->setIllustration($illustration);
							
							$this->em->persist($newPublisher);
						}
						
					}
				}
			}
		}

		$this->em->flush();
		
		$sql = "select be.id,
				(select p2.id from publisher p2 where p2.language_id = b.language_id and p.title = p2.title) as newPublisher
				from book_edition be
				join publisher p on p.id = be.publisher_id
				join book b on b.id = be.book_id
				 where b.language_id <> p.language_id
				order by be.id ASC;";
		
		$datas = $conn->fetchAllAssociative($sql);
		
		foreach($datas as $data) {
			$conn->exec("UPDATE book_edition SET publisher_id = ".$data["newPublisher"]." WHERE id = ".$data["id"]);
		}

        return 0;
    }
}