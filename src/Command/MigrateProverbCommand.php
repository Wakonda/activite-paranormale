<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Biography;
use App\Entity\Quotation;

class MigrateProverbCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
		parent::__construct();
        $this->em = $em;
    }

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:migrate-proverb';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$output->writeln("Start Book migration");

		$conn = $this->em->getConnection();
		
		$conn->exec("UPDATE quotation SET family = 'quotation' WHERE family IS NULL;");
		
		$biography = $this->em->getRepository(Biography::class)->find(193);
		$country = $this->em->getRepository(Region::class)->findOneBy(193);
		$quotations = $this->em->getRepository(Quotation::class)->findBy(["authorQuotation" => $biography]);
		
		foreach($quotations as $quotation) {
			$quotation->setFamily(Quotation::PROVERB_FAMILY);
			$quotation->setAuthorQuotation(null);
			$quotation->setCountry($country);
		}

        return 0;
    }
}