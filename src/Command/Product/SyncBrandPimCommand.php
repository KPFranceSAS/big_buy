<?php

namespace App\Command\Product;

use App\Synchronization\Product\BrandCreationFromPimSync;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sync-brands-pim',
    description: 'Get all brands from PIM',
)]
class SyncBrandPimCommand extends Command
{
    public function __construct(
        private BrandCreationFromPimSync $brandCreationFromPimSync,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->brandCreationFromPimSync->synchronize();
        return Command::SUCCESS;
    }
}
