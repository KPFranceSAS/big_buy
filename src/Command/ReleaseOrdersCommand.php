<?php

namespace App\Command;

;
use App\Synchronization\Order\OrdersCreation;
use App\Synchronization\Order\OrdersRelease;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:release-orders',
    description: 'Release orders',
)]
class ReleaseOrdersCommand extends Command
{

    private $manager;


    public function __construct(private OrdersRelease $ordersRelease)
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ordersRelease->synchronize();

        return Command::SUCCESS;
    }
}
