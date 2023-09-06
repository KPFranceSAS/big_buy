<?php

namespace App\Command\Order;

use App\Synchronization\Order\OrdersCreation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:orders-create',
    description: 'Create orders',
)]
class OrdersCreateCommand extends Command
{


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
