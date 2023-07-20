<?php

namespace App\Command;

use App\Synchronization\Product\ProductSync;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-products',
    description: 'Add a short description for your command',
)]
class SyncProductsCommand extends Command
{
    public function __construct(private ProductSync $productSync)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productSync->synchronize();
        return Command::SUCCESS;
    }
}
