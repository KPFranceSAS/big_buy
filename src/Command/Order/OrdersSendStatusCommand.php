<?php

namespace App\Command\Order;

use App\Synchronization\Order\OrdersStatusRelease;
use App\Synchronization\Order\OrdersStatusSent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:orders-send-status',
    description: 'Check send orders status',
)]
class OrdersSendStatusCommand extends Command
{

    private $manager;


    public function __construct(private OrdersStatusSent $ordersStatusSent)
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ordersStatusSent->synchronize();

        return Command::SUCCESS;
    }
}
