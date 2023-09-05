<?php

namespace App\Command;

use App\Synchronization\Order\OrdersStatusRelease;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:release-status-orders',
    description: 'Check release orders status',
)]
class ReleaseStatusOrdersCommand extends Command
{

    private $manager;


    public function __construct(private OrdersStatusRelease $ordersStatusRelease)
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ordersStatusRelease->synchronize();

        return Command::SUCCESS;
    }
}
