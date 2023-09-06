<?php

namespace App\Command\Order;


use App\Synchronization\Order\OrdersRelease;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:orders-release',
    description: 'Release orders',
)]
class OrdersReleaseCommand extends Command
{


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
