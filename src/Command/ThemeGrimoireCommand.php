<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\SurThemeGrimoire;
use App\Entity\MenuGrimoire;
use Ausi\SlugGenerator\SlugGenerator;

#[AsCommand(
   name: 'app:migrate-theme-grimoire'
)]
class ThemeGrimoireCommand extends Command
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
		$output->writeln("Start Theme migration");

		$conn = $this->em->getConnection();
		
		$conn->exec("UPDATE comment SET dateComment = '2012-12-21 00:00:00' WHERE CAST(dateComment AS CHAR(20)) = '0000-00-00 00:00:00'");

		$sql = "SELECT id, photo FROM witchcrafttool WHERE illustration_id IS NULL";
		$datas = $conn->fetchAllAssociative($sql);

		foreach($datas as $data)
		{
			$conn->exec("INSERT INTO `filemanagement` (`titleFile`, `realNameFile`, `extensionFile`, `kindFile`, `discr`, `caption`) VALUES ('".str_replace("'", "\'", $data['photo'])."', '".str_replace("'", "\'", $data['photo'])."', '".pathinfo(str_replace("'", "\'", $data['photo']), PATHINFO_EXTENSION)."', 'file', 'filemanagement', NULL)");
			$fmId = $conn->fetchOne("SELECT LAST_INSERT_ID()");

			$conn->exec("UPDATE witchcrafttool SET illustration_id = ".$fmId." WHERE id = ".$data["id"]);
		}
		
		echo "End witchcrafttool";

		/*$surThemes = $this->em->getRepository(MenuGrimoire::class)->findAll();
		
		foreach($surThemes as $surTheme) {
			$st = $this->em->getRepository(SurThemeGrimoire::class)->findOneBy(["internationalName" => $surTheme->getInternationalName(), "language" => $surTheme->getLanguage()]);

			if(empty($st))
				$st = new SurThemeGrimoire();
			
			$st->setTitle($surTheme->getTitle());
			$st->setInternationalName($surTheme->getInternationalName());
			$st->setLanguage($surTheme->getLanguage());
			$st->setText($surTheme->getText());
			$st->setPhoto($surTheme->getPhoto());
			
			$this->em->persist($st);
		}

		$this->em->flush();
		
		$themes = $this->em->getRepository(SurThemeGrimoire::class)->findAll();
		
		foreach($themes as $theme) {
			if(empty($theme->getParentTheme()))
				continue;

			$st = $this->em->getRepository(SurThemeGrimoire::class)->findOneBy(["internationalName" => $theme->getParentTheme()->getInternationalName(), "language" => $theme->getParentTheme()->getLanguage()]);

			$theme->setParentTheme($st);
			
			$this->em->persist($st);
		}

		$this->em->flush();*/

        return 0;
    }
}