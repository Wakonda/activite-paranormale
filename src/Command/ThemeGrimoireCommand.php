<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Theme;
use App\Entity\SurTheme;
use Ausi\SlugGenerator\SlugGenerator;

class ThemeGrimoireCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    protected static $defaultName = 'app:migrate-theme-grimoire';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start Theme migration");
		
		$surThemes = $this->em->getRepository(SurTheme::class)->findAll();
		
		foreach($surThemes as $surTheme) {
			$st = $this->em->getRepository(Theme::class)->findOneBy(["internationalName" => $surTheme->getInternationalName(), "language" => $surTheme->getLanguage()]);

			if(empty($st))
				$st = new Theme();
			
			$st->setTitle($surTheme->getTitle());
			$st->setInternationalName($surTheme->getInternationalName());
			$st->setLanguage($surTheme->getLanguage());
			
			$this->em->persist($st);
		}

		$this->em->flush();
		
		$themes = $this->em->getRepository(Theme::class)->findAll();
		
		foreach($themes as $theme) {
			if(empty($theme->getSurTheme()))
				continue;

			$st = $this->em->getRepository(Theme::class)->findOneBy(["internationalName" => $theme->getSurTheme()->getInternationalName(), "language" => $theme->getSurTheme()->getLanguage()]);

			$theme->setParentTheme($st);
			
			$this->em->persist($st);
		}

		$this->em->flush();

        return 0;
    }
}