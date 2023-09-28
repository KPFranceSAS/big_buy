<?php

namespace App\Command\Order;

use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Entity\SaleOrderLine;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:save-orders',
    description: 'Save orders',
)]
class SaveOrdersCommand extends Command
{

    public function __construct(private  ManagerRegistry $managerRegistry)
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manager = $this->managerRegistry->getManager();
        $saleOrders = $manager->getRepository(SaleOrder::class)->findAll();
        foreach($saleOrders as $saleOrder) {
            $saleOrder->recalculateTotal();
        }
        $manager->flush();


        $saleOrderLines = $manager->getRepository(SaleOrderLine::class)->findAll();
        foreach($saleOrderLines as $saleOrderLine) {
            if(!$saleOrderLine->getProduct()) {
                $product= $manager->getRepository(Product::class)->findOneBySku($saleOrderLine->getSku());
                if($product) {
                    $saleOrderLine->setProduct($product);
                }
            }
        }
        $manager->flush();


        return Command::SUCCESS;
    }



}
