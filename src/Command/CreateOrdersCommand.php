<?php

namespace App\Command;

use App\Entity\User;
use App\Synchronization\Order\OrdersCreation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-orders',
    description: 'Create orders',
)]
class CreateOrdersCommand extends Command
{

    private $manager;


    public function __construct(private OrdersCreation $ordersCreation)
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ordersCreation->synchronize();

        return Command::SUCCESS;
    }
}
