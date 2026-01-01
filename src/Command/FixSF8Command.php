<?php

namespace App\Command;

use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sf8',
    description: 'Convertit les rôles du format sérialisé PHP vers le format JSON',
)]
class FixSF8Command extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
		
		$conn = $this->entityManager->getConnection();

		$resultSet = $conn->executeQuery("SELECT * FROM ap_user");

		$users = $resultSet->fetchAllAssociative();

        $count = 0;

        foreach ($users as $user) {
			$d = @unserialize($user["roles"]);
				
			if(!empty($d))
				$conn->executeQuery("UPDATE ap_user SET roles = '".json_encode($d)."' WHERE id = ".$user["id"]);

            $count++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Terminé ! %d utilisateurs ont été mis à jour.', $count));

		$resultSet = $conn->executeQuery("SELECT * FROM historydetail");

		$users = $resultSet->fetchAllAssociative();

        $count = 0;

        foreach ($users as $user) {
			$d = @unserialize($user["diffText"]);
			
			$str = preg_replace('/s:\d+:"/', '', $user["diffText"]);
			$str = rtrim($str, '";');
			$str = str_replace("'", "''", $str);
// dd(json_encode($str));
			if(!empty($str))
				$conn->executeQuery("UPDATE historydetail SET diffText = '".$str."' WHERE id = ".$user["id"]);
            $count++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Terminé ! %d historiques ont été mis à jour.', $count));

        return Command::SUCCESS;
    }
}