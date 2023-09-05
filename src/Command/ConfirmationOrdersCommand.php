<?php

namespace App\Command;

use App\Synchronization\Order\OrdersStatusConfirmation;
use App\Synchronization\Order\OrdersStatusRelease;
use App\Synchronization\Order\OrdersStatusSent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:confirmation-orders',
    description: 'Check send orders confirmation',
)]
class ConfirmationOrdersCommand extends Command
{

    private $manager;


    public function __construct(private OrdersStatusConfirmation $ordersStatusSent)
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ordersStatusSent->synchronize();

        return Command::SUCCESS;
    }
}
