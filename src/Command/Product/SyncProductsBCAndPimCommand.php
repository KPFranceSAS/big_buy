<?php

namespace App\Command\Product;

use App\Synchronization\Product\ProductCreationFromBcSync;
use App\Synchronization\Product\ProductUpdateFromBcSync;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-products-bc-pim',
    description: 'Get products from Akeneo and save it on local db. Sync it with Business central',
)]
class SyncProductsBCAndPimCommand extends Command
{
    public function __construct(
        private ProductUpdateFromBcSync $productUpdateSync,
        private ProductCreationFromBcSync $productCreateSync
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productUpdateSync->synchronize();
        $this->productCreateSync->synchronize();
        return Command::SUCCESS;
    }
}
