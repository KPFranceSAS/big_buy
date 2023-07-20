<?php

namespace App\Synchronization\Prices;

;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Entity\Product;
use App\Mailer\SendEmail;
use App\Synchronization\Order\OrderCreationSync;
use App\Synchronization\Product\ProductSync;
use App\Transformer\PriceTransformer;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class PricesFromBcSync
{
    
    public const DEPOT_LAROCA = 'LAROCA';

    public const  DEPOT_3PLUE = '3PLUE';
    
    private $bcConnector;

    private $manager;

    public function __construct(
        private LoggerInterface $logger,
        private ProductSync $productSync,
        private BusinessCentralAggregator $businessCentralAggregator,
        private ManagerRegistry $managerRegistry,
        private FilesystemOperator $defaultStorage,
        private FilesystemOperator $bigBuyStorage,
        private SendEmail $sendEmail,
    ) {
        $this->bcConnector = $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KIT_PERSONALIZACION_SPORT);
        $this->manager = $this->managerRegistry->getManager();
    }

    
    public function synchronize()
    {
        $this->logger->info('---------------------------------------------------------------');
        $this->logger->info('Start synchro prices in BC');
        $this->logger->info('---------------------------------------------------------------');
        $errors = [];
        $products = $this->productSync->getProductsEnabledOnChannel();
        $csv = Writer::createFromString();
       

        $withHeader = false;

        $nbProductsUpdated = 0;
        foreach ($products as $product) {
            try {
                $productPrice =   $this->getPriceStock($product['identifier']);
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
        $this->defaultStorage->write('prices_stocks'.date('Ymd-His').'.csv', $csv->toString());
        $this->bigBuyStorage->write('products/prices_stocks.csv', $csv->toString());
        $this->logger->info('---------------------------------------------------------------');
        $this->logger->info('End synchro creation prices with '.$nbProductsUpdated.' prices on Magento');
        $this->logger->info('---------------------------------------------------------------');
    }


    public function getPriceStock($sku): ?array
    {
        $this->logger->info('Start with '.$sku);
        $itemBc = $this->bcConnector->getItemByNumber($sku);
        if(!$itemBc) {
            $this->logger->error('Product do no exists in BC');
            return null;
        }

        if($itemBc['itemStatus']=='Inactivo') {
            $this->logger->error('Product inactive in BC');
            return null;
        }


        $priceBB = $this->getBigBuyPrice($sku);
        if(!$priceBB) {
            $this->logger->error('No price is defined');
            return null;
        }


        return [
            'sku' => $sku,
            'specialVat' => $itemBc['vatProdPostingGroup']!='VAT21' ? 0 : 1,
            'stock' => $this->getFinalStockProductWarehouse($sku, $this->isBundle($itemBc)),
            'priceBB' =>  $priceBB,
            'pricePublic' =>  $this->getRetailPrice($sku),
            'canonDigital' => $this->getCanonDigitalForItem($itemBc)
        ];
        

        
    }



    public function getBigBuyPrice($sku)
    {
        $itemPricesGroup = $this->bcConnector->getPricesSkuPerGroup($sku, 'PVD-ES');
        $itemPricesCustomer = $this->bcConnector->getPricesSkuPerCustomer($sku, OrderCreationSync::CUSTOMER_NUMBER);
        
        $itemPrices = array_merge($itemPricesGroup, $itemPricesCustomer);
        return $this->getBestPrice($itemPrices);
    }



    public function getCanonDigitalForItem(array $item): ?float
    {
        if ($item && $item['DigitalCopyTax'] && strlen($item['CanonDigitalCode'])>0) {
            $taxes = $this->bcConnector->getTaxesByCodeAndByFeeType($item['CanonDigitalCode'], 'Canon Digital');
            if ($taxes && $taxes['UnitPrice'] > 0) {
                $this->logger->info('Canon digital de ' . $taxes['UnitPrice'] . ' for ' . $item['number']);
                return $taxes['UnitPrice'];
            } else {
                $this->logger->info('No canon digital found for ' . $item['CanonDigitalCode']);
            }
        } else {
            $this->logger->info('No canon digital for ' . $item['number']);
        }
        return null;
    }


    public function getRetailPrice($sku)
    {
        $itemPricesGroup = $this->bcConnector->getPricesSkuPerGroup($sku, 'PVP-ES');
        return $this->getBestPrice($itemPricesGroup, true);
    }



    public function getBestPrice($itemPrices, $calculateVat=false)
    {
        $bestPrice = null;
        $now = date('Y-m-d');
        foreach ($itemPrices as $itemPrice) {
            $itemPriceFormat = $this->transformPrice($itemPrice, $calculateVat);
            if (($itemPriceFormat['StartingDate']==null || $itemPriceFormat['StartingDate'] <= $now)
                && ($itemPriceFormat['EndingDate']==null || $itemPriceFormat['EndingDate'] >= $now)
                && $itemPriceFormat['CurrencyCode'] == 'EUR'
            ) {
                if (!$bestPrice) {
                    $bestPrice  = $itemPriceFormat['UnitPrice'];
                } elseif ($bestPrice > $itemPriceFormat['UnitPrice']) {
                    $bestPrice  = $itemPriceFormat['UnitPrice'];
                }
            }
        }

        return $bestPrice;
    }



    public function transformPrice($itemPrice, $calculateVat)
    {
        if (strlen($itemPrice['CurrencyCode'])==0) {
            $itemPrice['CurrencyCode']='EUR';
        }
        if ($itemPrice['EndingDate']=="0001-01-01") {
            $itemPrice['EndingDate'] = null;
        }

        if ($itemPrice['StartingDate']=="0001-01-01") {
            $itemPrice['StartingDate'] = null;
        }
        if ($itemPrice['PriceIncludesVAT']==false && $calculateVat) {
            $itemPrice['UnitPrice'] =  $itemPrice['UnitPrice']* 1.21;
        }


        return $itemPrice;
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



    /**
     * Check if it is a bundle
     */
    protected function isBundle($item): bool
    {
        if ($item['AssemblyBOM']==false) {
            return false;
        }

        if ($item['AssemblyBOM']==true && in_array($item['AssemblyPolicy'], ["Assemble-to-Stock", "Ensamblar para stock"])) {
            return false;
        }
        return true;
    }



}
