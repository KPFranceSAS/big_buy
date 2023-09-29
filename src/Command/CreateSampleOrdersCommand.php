<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-sample-orders',
    description: 'Create sample orders',
)]
class CreateSampleOrdersCommand extends Command
{

    private $manager;


    public function __construct(private FilesystemOperator $bigBuyStorage, private  ManagerRegistry $managerRegistry)
    {
        parent::__construct();
    }

   

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manager = $this->managerRegistry->getManager();
        $products = $manager->getRepository(Product::class)->findBy(['enabled'=> true ]);
        $nbOrders = rand(1, 5);
        $header = explode(";", "id;sku;price;quantity;address;city;contactPerson;country;mobileNo;phoneCountry;province;zip;email;firstName;lastName");
        for($i=0;$i<$nbOrders;$i++) {
            $productOrdersKey =  array_rand($products, rand(2, 5));
            $orderId = date('U').$i;
            $csv = Writer::createFromString();
            $csv->setDelimiter(';');
            $csv->insertOne($header);
            foreach($productOrdersKey as $productOrderKey) {
                $productOrder =  $products[$productOrderKey];
                //if()
                /** @var  \App\Entity\Product  $productOrder */
                $productOrder =  $products[$productOrderKey];
                $orderLine =array_fill_keys($header, null);
                $orderLine["id"] = $orderId;
                $orderLine["sku"] = $productOrder->getSku();
                $orderLine["quantity"] = rand(1, 2);
                $orderLine["price"] = $productOrder->getPrice() ? $productOrder->getPrice() : $productOrder->getResellerPrice();
                $csv->insertOne($orderLine);
            }
            $this->bigBuyStorage->write('Orders/Order_'.date('d_m_Y_His').'_'.$orderId.'.csv', $csv->toString());
        }
        

        return Command::SUCCESS;
    }



}
