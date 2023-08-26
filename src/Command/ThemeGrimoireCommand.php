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
		
		$surThemes = $this->em->getRepository(MenuGrimoire::class)->findAll();
		
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
			if(empty($theme->getMenuGrimoire()))
				continue;

			$st = $this->em->getRepository(SurThemeGrimoire::class)->findOneBy(["internationalName" => $theme->getMenuGrimoire()->getInternationalName(), "language" => $theme->getMenuGrimoire()->getLanguage()]);

			$theme->setParentTheme($st);
			
			$this->em->persist($st);
		}

		$this->em->flush();

        return 0;
    }
}