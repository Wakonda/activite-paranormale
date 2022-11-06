<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Book;
use App\Entity\Movies\GenreAudiovisual;
use App\Entity\FileManagement;

class MigrateBookGenreAudiovisualPhotosToFilemanagementCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:migrate-book-genreaudiovisual';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start Book migration");

		$conn = $this->em->getConnection();

		/*$sql = "SELECT id, photo FROM Book WHERE illustration_id IS NULL";
		$datas = $conn->fetchAll($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE Book SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		die("End Book");*/

		$conn = $this->em->getConnection();

		$sql = "SELECT id, photo FROM GenreAudiovisual WHERE illustration_id IS NULL";
		$datas = $conn->fetchAll($sql);
		
		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchColumn("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE GenreAudiovisual SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		die("End GenreAudiovisual");

        return 0;
    }
}