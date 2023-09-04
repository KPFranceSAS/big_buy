<?php

namespace App\Synchronization\Product;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Brand;
use App\Entity\Product;
use App\Mailer\SendEmail;
use App\Synchronization\Product\ProductExportSync;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class ProductCreationFromBcSync
{
    
    private $manager;

    public function __construct(
        private LoggerInterface $logger,
        private ProductExportSync $productSync,
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
        $products = $this->productSync->getProductsEnabledOnChannel();
        $nbProductsCreated = 0;
        foreach ($products as $product) {
            try {

                $sku = $product['identifier'];
                $itemBc = $this->bcConnector->getItemByNumber($sku);
                if(!$itemBc) {
                    $this->logger->error('Product do no exists in BC');
                }

                $productDb = $this->manager->getRepository(Product::class)->findOneBySku($sku);

                if(!$productDb) {
                    $productDb=new Product();
                    $productDb->setSku($sku);
                    $productDb->setNameErp($itemBc['displayName']);
                    $this->manager->persist($productDb);

                    $brandName = strtoupper($this->getAttributeSimple($product, 'brand'));

                    $brandDb = $this->manager->getRepository(Brand::class)->findOneByName($brandName);
                    if(!$brandDb) {
                        $brandDb=new Brand();
                        $brandDb->setName($brandName);
                        $this->manager->persist($brandDb);
                    }
                    $productDb->setBrand($brandDb);
                    $this->manager->flush();
                }

                $productDb->setBundle($this->isBundle($itemBc));
                $productDb->setPublicPrice($this->getRetailPrice($sku));
                $productDb->setCanonDigital($this->getCanonDigitalForItem($itemBc));
                $productDb->setVatCode($itemBc['vatProdPostingGroup']);
                
        
                if($itemBc['itemStatus']=='Inactivo') {
                    $productDb->setEnabled(false);
                    $productDb->setActiveInBc(false);
                } else {
                    $productDb->setActiveInBc(true);
                }

                

                
            } catch (Exception $e) {
                $errors [] = $e->getMessage();
                $this->logger->critical('Error '.$e->getMessage());
            }

             $this->manager->flush();
        }
        $this->logger->info('---------------------------------------------------------------');
        $this->logger->info('End synchro creation prices with '.$nbProductsCreated.' prices on app');
        $this->logger->info('---------------------------------------------------------------');
    }


    protected function getCanonDigitalForItem(array $item): ?float
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
        return 0;
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


    
    protected function getAttributeSimple($productPim, $nameAttribute, $locale=null)
    {
        if (array_key_exists($nameAttribute, $productPim['values'])) {
            if ($locale) {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                    if ($attribute['locale']==$locale) {
                        return $attribute['data'];
                    }
                }
            } else {
                return  $productPim['values'][$nameAttribute][0]["data"];
            }
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
                && ($itemPriceFormat['EndingDate']==null || $itemPriceFormat['EndingDate'] > $now)
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


}
