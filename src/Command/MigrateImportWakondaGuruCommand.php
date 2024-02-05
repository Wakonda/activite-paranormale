<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;
use Ausi\SlugGenerator\SlugGenerator;

use App\Entity\UsefulLink;
use App\Entity\Language;
use App\Entity\FileManagement;

#[AsCommand(
   name: 'app:migrate-import-wakondaguru'
)]
class MigrateImportWakondaGuruCommand extends Command
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
		$output->writeln("Start WakondaGuru migration");

		$conn = $this->em->getConnection();
		
		$conn->exec("UPDATE FileManagement SET realNameFile = titleFile WHERE realNameFile IS NULL AND titleFile IS NOT NULL;");
		
		$conn->exec("UPDATE UsefulLink SET category = 'usefullink' WHERE category = 'resource'");

		/*if (($handle = fopen(__DIR__.DIRECTORY_SEPARATOR."wakondaguru.csv", "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				$entity = $this->em->getRepository(UsefulLink::class)->findOneBy(["internationalName" => $data[12]]);
				
				if(empty($entity))
					$entity = new UsefulLink();

				$entity->setTitle($data[1]);
				$entity->setText((!empty($data[7]) ? $data[7]."<br><br>" : "").$data[2]);
				$entity->setInternationalName($data[12]);
				$entity->setCategory("development");
				$entity->setLanguage($this->em->getRepository(Language::class)->find(1));
				
				$tagsData = [];
				
				$tags = explode(",", $data[13]);

				foreach($tags as $tag)
					$tagsData[]= ["value" => $tag];
					
				$entity->setTags(json_encode($tagsData));

				$this->em->persist($entity);
				
				if(!empty($data[6])) {
					$url = "https://wakonda.guru/assets/articles/".$data[6];
					$headers = get_headers($url, 1);
					
					if ($headers[0] != 'HTTP/1.1 200 OK') {
						$url = "https://wakonda.guru/images/articles/".$data[6];
						$headers = get_headers($url, 1);
					}
					
					if ($headers[0] == 'HTTP/1.1 200 OK') {
						file_put_contents($entity->getTmpUploadRootDir().$data[6], 
						file_get_contents($url));

						$illustration = $entity->getIllustration();
						
						if(empty($illustration))
							$illustration = new FileManagement();
						
						$illustration->setTitleFile($data[6]);
						$illustration->setRealNameFile($data[6]);
						
						$illustration->setExtensionFile(pathinfo($url, PATHINFO_EXTENSION));
						$illustration->setKindFile("file");

						$this->em->persist($illustration);

						$entity->setIllustration($illustration);
					}
				}
			}
			
			fclose($handle);
			
			$this->em->flush();
		}*/
		
		/*if (($handle = fopen(__DIR__.DIRECTORY_SEPARATOR."wakondaguru_usefullink.csv", "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				$entity = $this->em->getRepository(UsefulLink::class)->findOneBy(["internationalName" => $data[8]]);

				if(empty($entity))
					$entity = new UsefulLink();

				$entity->setTitle($data[3]);
				$entity->setText($data[7]);
				$entity->setInternationalName($data[8]);
				$entity->setCategory("usefullink");
				$entity->setLanguage($this->em->getRepository(Language::class)->find(1));
				
				$links = [["title" => "", "url" => $data[5], "license" => ""]];
				
				$entity->setLinks(json_encode($links));

				$this->em->persist($entity);
				
				if(!empty($data[6])) {
					$url = "https://wakonda.guru/assets/usefullinks/".$data[6];
					$headers = get_headers($url, 1);
					
					if ($headers[0] != 'HTTP/1.1 200 OK') {
						$url = "https://wakonda.guru/images/usefullinks/".$data[6];
						$headers = get_headers($url, 1);
					}
					
					if ($headers[0] == 'HTTP/1.1 200 OK') {
						file_put_contents($entity->getTmpUploadRootDir().$data[6], 
						file_get_contents($url));

						$illustration = $entity->getIllustration();
						
						if(empty($illustration))
							$illustration = new FileManagement();
						
						$illustration->setTitleFile($data[6]);
						$illustration->setRealNameFile($data[6]);
						
						$illustration->setExtensionFile(pathinfo($url, PATHINFO_EXTENSION));
						$illustration->setKindFile("file");

						$this->em->persist($illustration);

						$entity->setIllustration($illustration);
					}
				}
			}
			
			fclose($handle);
			
			$this->em->flush();
		}*/
		
		if (($handle = fopen(__DIR__.DIRECTORY_SEPARATOR."wakondaguru_tool.csv", "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				$entity = $this->em->getRepository(UsefulLink::class)->findOneBy(["internationalName" => $data[8]]);

				if(empty($entity))
					$entity = new UsefulLink();

				$entity->setTitle($data[1]);
				$entity->setText($data[2]);
				$entity->setInternationalName($data[8]);
				$entity->setCategory("tool");
				$entity->setLanguage($this->em->getRepository(Language::class)->find(1));
				
				$links = [["title" => "", "url" => $data[4], "license" => ""]];
				
				$entity->setLinks(json_encode($links));

				$this->em->persist($entity);
				
				if(!empty($data[3])) {
					$url = "https://wakonda.guru/assets/usefullinks/".$data[3];
					$headers = get_headers($url, 1);
					
					if ($headers[0] != 'HTTP/1.1 200 OK') {
						$url = "https://wakonda.guru/images/usefullinks/".$data[3];
						$headers = get_headers($url, 1);
					}
					
					if ($headers[0] == 'HTTP/1.1 200 OK') {
						file_put_contents($entity->getTmpUploadRootDir().$data[3], 
						file_get_contents($url));

						$illustration = $entity->getIllustration();
						
						if(empty($illustration))
							$illustration = new FileManagement();
						
						$illustration->setTitleFile($data[3]);
						$illustration->setRealNameFile($data[3]);
						
						$illustration->setExtensionFile(pathinfo($url, PATHINFO_EXTENSION));
						$illustration->setKindFile("file");

						$this->em->persist($illustration);

						$entity->setIllustration($illustration);
					}
				}
			}
			
			fclose($handle);
			
			$this->em->flush();
		}

        return 0;
    }
}
