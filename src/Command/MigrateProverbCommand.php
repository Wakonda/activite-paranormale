<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

use App\Entity\Biography;
use App\Entity\Quotation;
use App\Entity\Region;
use App\Entity\EventMessage;

#[AsCommand(
   name: 'app:migrate-proverb'
)]
class MigrateProverbCommand extends Command
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
		$output->writeln("Start Proverb migration");

		$conn = $this->em->getConnection();

		$wts = $this->em->getRepository(EventMessage::class)->findAll();
		
		foreach($wts as $wt) {
			if(!empty($wt->getSlug()))
				continue;
			
			$wt->setSlug();

			$this->em->persist($wt);
		}
			$this->em->flush();
die("kkk");

		$conn->exec("UPDATE region SET family = 'country' WHERE family IS NULL;");

		$conn->exec("UPDATE quotation SET family = 'quotation' WHERE family IS NULL;");
		
		$biography = $this->em->getRepository(Biography::class)->find(193);
		$country = $this->em->getRepository(Region::class)->findOneBy(["title" => "Monde Arabe"]);
		$quotations = $this->em->getRepository(Quotation::class)->findBy(["authorQuotation" => $biography]);
		
		foreach($quotations as $quotation) {
			$quotation->setFamily(Quotation::PROVERB_FAMILY);
			$quotation->setAuthorQuotation(null);
			$quotation->setCountry($country);

			$this->em->persist($quotation);
		}
		
		$this->em->flush();
		
		$biography = $this->em->getRepository(Biography::class)->find(273);
		$country = $this->em->getRepository(Region::class)->findOneBy(["title" => "Chine"]);
		$quotations = $this->em->getRepository(Quotation::class)->findBy(["authorQuotation" => $biography]);
		
		foreach($quotations as $quotation) {
			$quotation->setFamily(Quotation::PROVERB_FAMILY);
			$quotation->setAuthorQuotation(null);
			$quotation->setCountry($country);

			$this->em->persist($quotation);
		}
		
		$this->em->flush();
		
		$biography = $this->em->getRepository(Biography::class)->find(64);
		$country = $this->em->getRepository(Region::class)->findOneBy(["title" => "Orient"]);
		$quotations = $this->em->getRepository(Quotation::class)->findBy(["authorQuotation" => $biography]);
		
		foreach($quotations as $quotation) {
			$quotation->setFamily(Quotation::PROVERB_FAMILY);
			$quotation->setAuthorQuotation(null);
			$quotation->setCountry($country);

			$this->em->persist($quotation);
		}
		
		$this->em->flush();

        return 0;
    }
}