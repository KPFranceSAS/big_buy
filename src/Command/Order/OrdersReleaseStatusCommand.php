<?php

namespace App\Command\Order;

use App\Synchronization\Order\OrdersStatusRelease;
use App\Synchronization\Order\OrdersStatusReleaseManually;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:orders-release-status',
    description: 'Check release orders status',
)]
class OrdersReleaseStatusCommand extends Command
{

    private $manager;


    public function __construct(
        private OrdersStatusRelease $ordersStatusRelease,
        private OrdersStatusReleaseManually $ordersStatusReleaseManually,

        )
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ordersStatusRelease->synchronize();
        $this->ordersStatusReleaseManually->synchronize();

        return Command::SUCCESS;
    }
}
