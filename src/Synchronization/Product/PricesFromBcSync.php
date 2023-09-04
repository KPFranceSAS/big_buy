<?php

namespace App\Synchronization\Product;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Product;
use App\Mailer\SendEmail;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class PricesFromBcSync
{
    
    public const DEPOT_LAROCA = 'LAROCA';

    public const  DEPOT_3PLUE = '3PLUE';
    

    private $manager;

    public function __construct(
        private LoggerInterface $logger,
        private KitPersonalizacionSportConnector $bcConnector,
        private ManagerRegistry $managerRegistry,
        private FilesystemOperator $defaultStorage,
        private FilesystemOperator $bigBuyStorage,
        private SendEmail $sendEmail,
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }

    
    public function synchronize()
    {
        $this->logger->info('---------------------------------------------------------------');
        $this->logger->info('Start synchro prices in BC');
        $this->logger->info('---------------------------------------------------------------');
        $errors = [];
        $products = $this->manager->getRepository(Product::class)->findBy([
            'enabled'=> true,
        ]);
        $csv = Writer::createFromString();
       

        $withHeader = false;

        $nbProductsUpdated = 0;
        foreach ($products as $product) {
            try {
                $productPrice =   $this->getPriceStock($product);
                if($productPrice) {
                    if(!$withHeader) {
                        $header = array_keys($productPrice);
                        $csv->insertOne($header) ;
                        $withHeader = true;
                    }
                    $csv->insertOne($productPrice);
                    $nbProductsUpdated++;
                }
            } catch (Exception $e) {
                $errors [] = $e->getMessage();
                $this->logger->critical('Error '.$e->getMessage());
            }
        }
        $this->defaultStorage->write('prices/prices_stocks'.date('Ymd-His').'.csv', $csv->toString());
        $this->bigBuyStorage->write('Products/prices_stocks.csv', $csv->toString());
        $this->logger->info('---------------------------------------------------------------');
        $this->logger->info('End synchro creation prices with '.$nbProductsUpdated.' prices to Big buy');
        $this->logger->info('---------------------------------------------------------------');
    }


    public function getPriceStock(Product $product): ?array
    {
        $this->logger->info('Start with '.$product->getSku());
       

        if(!$product->getPrice()) {
            $this->logger->error('Product has no price');
            return null;
        }

        return [
            'sku' => $product->getSku(),
            'specialVat' => $product->getVatCode()=='ESPECIAL' ? 1 : 0,
            'stock' => $this->getFinalStockProductWarehouse($product->getSku(), $product->isBundle()),
            'priceBB' =>  $product->getPrice(),
            'pricePublic' => $product->getPublicPrice(),
            'canonDigital' => $product->getCanonDigital()
        ];
        

        
    }




    


    




    protected function getStockAvailability($sku, $depot = self::DEPOT_LAROCA): int
    {
        $this->logger->info('Retrieve data from BC ' . $sku . ' in ' . $depot);
        $skuAvalibility =  $this->bcConnector->getStockAvailabilityPerProduct($sku);
        if ($skuAvalibility) {
            $this->logger->info('Stock available ' . $skuAvalibility['no'] . ' in ' . $depot . ' >>> ' . $skuAvalibility['quantityAvailable'.$depot]);
            return $skuAvalibility['quantityAvailable'.$depot];
        } else {
            $this->logger->error('Not found ' . $sku . ' in ' . $depot);
        }
        return 0;
    }


    

    /**
     * Returns the ponderated level of stock of product or bundle
     */
    public function getFinalStockProductWarehouse($sku, $isBundle, $depot = self::DEPOT_LAROCA): int
    {
        $this->logger->info('------ Check stock '.$sku.' in depot '.$depot.' ------ ');
        if ($isBundle) {
            $this->logger->info('Sku '.$sku.' is bundle');
            $stock =  $this->getFinalStockBundleWarehouse($sku, $depot);
        } else {
            $stock = $this->getFinalStockComponentWarehouse($sku, $depot);
        }
        $this->logger->info('Stock '.$sku.' in depot '.$depot.' >>> '.$stock);

        return $stock;
    }

    /**
     * Returns the level of stock of simple product
     */
    protected function getFinalStockComponentWarehouse($sku, $depot = self::DEPOT_LAROCA): int
    {
        $stock = $this->getStockAvailability($sku, $depot);
        return ($stock > 10) ? ($stock - 10) : 0;
    }



    /**
     * Returns the level of stock of bundle product
     */
    protected function getFinalStockBundleWarehouse($sku, $depot): int
    {
        $components = $this->bcConnector->getComponentsBundle($sku);
        
        $availableStock = PHP_INT_MAX;
        foreach ($components as $component) {
            if ($component['Quantity'] == 0) {
                $availableStock = 0;
                break;
            }
            $stock = $this->getFinalStockComponentWarehouse($component['ComponentSKU'], $depot);
            $componentStock = floor($stock / $component['Quantity']);
            $this->logger->info("Component ".$component['ComponentSKU']." capacity in ".$componentStock);

            if ($componentStock < $availableStock) {
                $availableStock = $componentStock;
            }
        }

        $this->logger->info("Avaibility bundle ".$availableStock);

        return $availableStock;
    }



    


}
