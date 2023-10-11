<?php

namespace App\Command\Product;

use App\Synchronization\Product\ProductExportSync;
use App\Synchronization\Product\ProductMajBigBuySync;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:maj-products-bigbuy-pim',
    description: 'Maj product big buy on PIM',
)]
class MajProductsBigBuyPimCommand extends Command
{
    public function __construct(private ProductMajBigBuySync $productMajBigBuySync)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productMajBigBuySync->synchronize();
        return Command::SUCCESS;
    }
}
