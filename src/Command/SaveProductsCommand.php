<?php

namespace App\Command;

use App\Synchronization\Product\ProductCreationFromBcSync;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:save-products',
    description: 'Get products from Akeneo and save it on local db. Sync it with Business central',
)]
class SaveProductsCommand extends Command
{
    public function __construct(private ProductCreationFromBcSync $productSync)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productSync->synchronize();
        return Command::SUCCESS;
    }
}
