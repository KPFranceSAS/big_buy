<?php

namespace App\Command;

use App\Synchronization\Product\PricesFromBcSync;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-prices-stocks-to-bigbuy',
    description: 'Send a sync file for stock and price',
)]
class SyncPricesCommand extends Command
{
    public function __construct(private PricesFromBcSync $productSync)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productSync->synchronize();
        return Command::SUCCESS;
    }
}
