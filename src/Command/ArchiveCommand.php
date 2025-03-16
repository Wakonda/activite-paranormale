<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\News;
use App\Entity\Photo;
use App\Entity\Biography;
use App\Entity\FileManagement;

use Ausi\SlugGenerator\SlugGenerator;

#[AsCommand(
   name: 'app:archive'
)]
class ArchiveCommand extends Command
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
		$output->writeln("Start Archive migration");

		$conn = $this->em->getConnection();

		$generator = new SlugGenerator;
		$wts = $this->em->getRepository("\App\Entity\WitchcraftTool")->findAll();
		
		foreach($wts as $wt) {
			$conn->exec("UPDATE witchcrafttool SET slug = '".$generator->generate($wt->getTitle())."' WHERE id = ".$wt->getId());
		}

		$conn->exec("DELETE FROM vote WHERE idClassVote IN (26, 7346, 359, 765, 893, 2196, 5392, 6048, 9814, 10734, 2947);");
		$conn->exec("DELETE FROM newsvote WHERE news_id IN (26, 7346, 359, 765, 893, 2196, 5392, 6048, 9814, 10734, 2947);");
		$conn->exec("DELETE FROM newscomment WHERE news_id IN (26, 7346, 359, 765, 893, 2196, 5392, 6048, 9814, 10734, 2947);");
		$conn->exec("DELETE FROM comment WHERE id IN (SELECT id FROM newscomment WHERE news_id IN (26, 7346, 359, 765, 893, 2196, 5392, 6048, 9814, 10734, 2947));");
		$conn->exec("DELETE FROM news WHERE id IN (26, 7346, 359, 765, 893, 2196, 5392, 6048, 9814, 10734, 2947);");
		
		$conn->exec("UPDATE book SET archive = true WHERE YEAR(writingDate) < 2020;");
		$conn->exec("UPDATE document SET archive = false;");
		$conn->exec("UPDATE eventmessage SET archive = true;");
		$conn->exec("UPDATE cartography SET archive = false;");
		$conn->exec("UPDATE movie SET archive = false;");
		$conn->exec("UPDATE news SET archive = true WHERE YEAR(publicationDate) < 2020;");
		$conn->exec("UPDATE news SET archive = false WHERE YEAR(publicationDate) >= 2020;");
		$conn->exec("UPDATE photo SET archive = true WHERE YEAR(publicationDate) < 2020;");
		$conn->exec("UPDATE photo SET archive = false WHERE YEAR(publicationDate) >= 2020;");
		$conn->exec("UPDATE testimony SET archive = false;");
		$conn->exec("UPDATE video SET archive = true WHERE YEAR(publicationDate) < 2020;");
		$conn->exec("UPDATE video SET archive = false WHERE YEAR(publicationDate) >= 2020;");
		$conn->exec("UPDATE news SET source = sourceNew;");
		
		$conn->exec("UPDATE tags SET tagword_id = 170 WHERE tagword_id = 155;");
		$conn->exec("DELETE FROM tagword WHERE id = 155;");

		$conn->exec("UPDATE tags SET tagword_id = 180 WHERE tagword_id = 30;");
		$conn->exec("DELETE FROM tagword WHERE id = 30;");

		$conn->exec("UPDATE tags SET tagword_id = 152 WHERE tagword_id = 45;");
		$conn->exec("DELETE FROM tagword WHERE id = 45;");

		$conn->exec("UPDATE tags SET tagword_id = 61 WHERE tagword_id = 108;");
		$conn->exec("DELETE FROM tagword WHERE id = 108;");

		$conn->exec("UPDATE tags SET tagword_id = 7 WHERE tagword_id = 19;");
		$conn->exec("DELETE FROM tagword WHERE id = 19;");

		$conn->exec("UPDATE tags SET tagword_id = 73 WHERE tagword_id = 38;");
		$conn->exec("DELETE FROM tagword WHERE id = 38;");

		$conn->exec("UPDATE tags SET tagword_id = 162 WHERE tagword_id = 8;");
		$conn->exec("DELETE FROM tagword WHERE id = 8;");
		
        return 0;
    }
}