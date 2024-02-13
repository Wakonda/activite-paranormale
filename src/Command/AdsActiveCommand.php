<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Language;
use App\Entity\ClassifiedAds;
use App\Entity\ClassifiedAdsCategory;

#[AsCommand(
   name: 'app:ads-active'
)]
class AdsActiveCommand extends Command
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
		$output->writeln("Start Activate Ads");
		
		$conn = $this->em->getConnection();
		// $conn->exec("UPDATE Advertising SET active = true;");

		$fr = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "fr"]);
		$en = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "en"]);
		$es = $this->em->getRepository(Language::class)->findOneBy(["abbreviation" => "es"]);

		$categoriesFr = [
			"Bien-être / Santé" => [
				"Maître Reiki",
				"Guérisseur",
				"Magnétiseur",
				"Hypnotiseur",
				"Chaman",
				"Énergéticien",
				"Coupeur de feu",
				"Exorciste"
			],
			"Divination / Spiritisme" => [
				"Voyante / Voyant",
				"Medium",
				"Astrologue",
				"Tarologue",
				"Sourcier",
				"Numérologue",
				"Marabout"
			],
			"Loisir" => [
				"Livre"
			]
		];

		foreach(array_keys($categoriesFr) as $parentCategory) {
			$pc = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $parentCategory, "language" => $fr]);
			
			if(empty($pc))
				$pc = new ClassifiedAdsCategory();
			
			$pc->setLanguage($fr);
			$pc->setTitle($parentCategory);
			$this->em->persist($pc);
		}
		
		$this->em->flush();

		foreach($categoriesFr as $parentCategory => $categories) {
			$pc = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $parentCategory, "language" => $fr]);
			
			foreach($categories as $category) {
				$c = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $category, "language" => $fr, "parentCategory" => $pc]);

				if(empty($c))
					$c = new ClassifiedAdsCategory();

				$c->setLanguage($fr);
				$c->setTitle($category);
				$c->setParentCategory($pc);
				$this->em->persist($c);
			}
		}
		
		$this->em->flush();

		$categoriesEn = [
			"Well-being / Health" => [
				"Reiki Master",
				"Healer",
				"Magnetizer",
				"Hypnotist",
				"Shaman",
				"Energy specialist",
				"Fire-cutter",
				"Exorcist"
			],
			"Divination / Spiritism" => [
				"Voyante / Voyant",
				"Medium",
				"Astrologist",
				"Tarot reader",
				"Water diviner",
				"Numerologist",
				"Marabout"
			],
			"Leisure" => [
				"Book"
			]
		];

		foreach(array_keys($categoriesEn) as $parentCategory) {
			$pc = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $parentCategory, "language" => $en]);
			
			if(empty($pc))
				$pc = new ClassifiedAdsCategory();
			
			$pc->setLanguage($en);
			$pc->setTitle($parentCategory);
			$this->em->persist($pc);
		}
		
		$this->em->flush();

		foreach($categoriesEn as $parentCategory => $categories) {
			$pc = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $parentCategory, "language" => $en]);
			
			foreach($categories as $category) {
				$c = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $category, "language" => $en, "parentCategory" => $pc]);

				if(empty($c))
					$c = new ClassifiedAdsCategory();

				$c->setLanguage($en);
				$c->setTitle($category);
				$c->setParentCategory($pc);
				$this->em->persist($c);
			}
		}
		
		$this->em->flush();

		$categoriesEs = [
			"Bienestar / Salud" => [
				"Maestro Reiki",
				"Curandero",
				"Magnetizador",
				"Hipnotizador",
				"Chamán",
				"Especialista en energía",
				"Cortador de fuego",
				"Exorcista"
			],
			"Adivinación / Espiritismo" => [
				"Adivino / adivina",
				"Médium",
				"Astrólogo",
				"Lector de tarot",
				"Zahorí",
				"Numerólogo",
				"Morabito"
			],
			"Ocio" => [
				"Libro"
			]
		];

		foreach(array_keys($categoriesEs) as $parentCategory) {
			$pc = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $parentCategory, "language" => $es]);
			
			if(empty($pc))
				$pc = new ClassifiedAdsCategory();
			
			$pc->setLanguage($es);
			$pc->setTitle($parentCategory);
			$this->em->persist($pc);
		}
		
		$this->em->flush();

		foreach($categoriesEs as $parentCategory => $categories) {
			$pc = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $parentCategory, "language" => $es]);
			
			foreach($categories as $category) {
				$c = $this->em->getRepository(ClassifiedAdsCategory::class)->findOneBy(["title" => $category, "language" => $es, "parentCategory" => $pc]);

				if(empty($c))
					$c = new ClassifiedAdsCategory();

				$c->setLanguage($es);
				$c->setTitle($category);
				$c->setParentCategory($pc);
				$this->em->persist($c);
			}
		}
		
		$this->em->flush();


        return 0;
    }
	
	private function isPost($categories): bool 
	{
		foreach($categories as $category)
			if(parse_url($category->getAttribute("term"), PHP_URL_FRAGMENT) == "post")
				return true;

		return false;
	}
}