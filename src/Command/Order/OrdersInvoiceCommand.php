<?php

namespace App\Command\Order;

use App\Synchronization\Order\OrdersStatusInvoice;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'app:orders-invoice',
    description: 'Check send invoices',
)]
class OrdersInvoiceCommand extends Command
{

    private $manager;


    public function __construct(private OrdersStatusInvoice $ordersStatusSent)
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ordersStatusSent->synchronize();

        return Command::SUCCESS;
    }
}
