<?php

namespace App\Command\Product;

use App\Synchronization\Product\ProductCreationFromBcSync;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-products-bc-pim',
    description: 'Create products from Akeneo and save it on local db. Sync it with Business central',
)]
class CreateProductsBCAndPimCommand extends Command
{
    public function __construct(
        private ProductCreationFromBcSync $productCreateSync
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productCreateSync->synchronize();
        return Command::SUCCESS;
    }
}
