<?php

namespace App\Command;

use App\Entity\User;
use App\Helper\Utils\CalculatorNext;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:testdate',
    description: 'Create a new user',
)]
class TestDateCommand extends Command
{



    public function __construct(private string $closingHours)
    {
        parent::__construct();
    }

    

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $datetime = DateTime::createFromFormat('Y-m-d H:i', '2024-04-30 11:40');
        $dateRelease =  CalculatorNext::getNextDelivery($datetime, $this->closingHours);
        dump($dateRelease);

        $datetime = DateTime::createFromFormat('Y-m-d H:i', '2024-08-14 11:40');
        $dateRelease =  CalculatorNext::getNextDelivery($datetime, $this->closingHours);
        dump($dateRelease);

        return Command::SUCCESS;
    }
}
