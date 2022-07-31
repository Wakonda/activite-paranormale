<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use App\Entity\Page;
use App\Entity\Language;
use App\Entity\Licence;
use App\Entity\State;

class CreateDevelopmentPageCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    protected static $defaultName = 'app:create-development-page';

    protected function configure()
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start create development page");
		
		$internationalName = "development";
		
		$datas = [
			[
				"title" => "Développement",
				"text" => "<p>Vous pouvez participer à <b>Activité-Paranormale</b> de plusieurs manières. Si vous êtes passionné par le développement web, vous pouvez apporter votre contribution sur <a href='https://github.com/Wakonda/activite-paranormale' target='_blank'>Github</a>. Que vous soyez plus à l’aise en PHP ou que vous avez la fibre artistique, nous serons ravis de vous compter parmi celles et ceux qui fera grandir et prospérer ce site Internet.

							<p><u>Activité-Paranormale</u> utilise plusieurs technologies :</p>
							<ul>
							<li>Symfony</li>
							<li>Doctrine</li>
							<li>CSS</li>
							<li>HTML5</li>
							<li>JavaScript</li>
							<li>PHP 7+</li>
							</ul>
							<p>N’hésitez pas à proposer votre contribution peu importe si vous êtes un professionnel, un débutant ou un passionné.</p>",
				"language" => "fr"
			],
			[
				"title" => "Development",
				"text" => "<p>You can participate in <b>Activité-Paranormale</b> in several ways. If you are passionate about web development, you can contribute on <a href='https://github.com/Wakonda/activite-paranormale' target='_blank'>Github</a>. Whether you are more comfortable in PHP or have an artistic flair, we will be delighted to count you among those who will make this website grow and prosper.</p>
				           <p><u>Activité-Paranormale</u> uses several technologies:</p>
						   	<ul>
							<li>Symfony</li>
							<li>Doctrine</li>
							<li>CSS</li>
							<li>HTML5</li>
							<li>JavaScript</li>
							<li>PHP 7+</li>
							</ul>
							<p>Don’t hesitate to offer your contribution regardless of whether you are a professional, a beginner or an enthusiast.</p>",
				"language" => "en"
			],
			[
				"title" => "Desarrollo",
				"text" => "<p>Puedes participar en <b>Activité-Paranormale</b> de varias maneras. Si te apasiona el desarrollo web, puedes contribuir en <a href='https://github.com/Wakonda/activite-paranormale' target='_blank'>Github</a>. Ya sea que se sienta más cómodo con PHP o tenga un don artístico, estaremos encantados de contarlo entre aquellos que harán que este sitio web crezca y prospere.</p>
				           <p><u>Activité-Paranormale</u> utiliza varias tecnologías:</p>
						   	<ul>
							<li>Symfony</li>
							<li>Doctrine</li>
							<li>CSS</li>
							<li>HTML5</li>
							<li>JavaScript</li>
							<li>PHP 7+</li>
							</ul>
							<p>No dudes en ofrecer tu aportación sin importar si eres un profesional, un principiante o un aficionado.</p>",
				"language" => "es"
			]
		];
		
		foreach($datas as $data) {
			$language = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => $data["language"]]);
			$entity = $this->em->getRepository(Page::class)->findOneBy(["internationalName" => $internationalName, "language" => $language]);
			
			if(empty($entity))
				$entity = new Page();
			
			$state = $this->em->getRepository(State::class)->findOneBy(["internationalName" => "Validate", "language" => $language]);
			$entity->setState($state);
			
			$licence = $this->em->getRepository(Licence::class)->findOneBy(["internationalName" => "CC-BY", "language" => $language]);
			$entity->setLicence($licence);
			
			$entity->setLanguage($language);
			
			$entity->setTitle($data["title"]);
			$entity->setText($data["text"]);
			
			$entity->setWritingDate(new \DateTime());
			$entity->setPublicationDate(new \DateTime());
			
			$entity->setInternationalName($internationalName);
			
			$this->em->persist($entity);
		}
		
		$this->em->flush();

        return 0;
    }
}