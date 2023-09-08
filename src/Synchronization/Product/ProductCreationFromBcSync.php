<?php

namespace App\Synchronization\Product;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Brand;
use App\Entity\Product;
use App\Mailer\SendEmail;
use App\Pim\AkeneoConnector;
use App\Synchronization\Product\ProductExportSync;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class ProductCreationFromBcSync
{
    
    protected $manager;

    public function __construct(
        protected LoggerInterface $logger,
        protected ProductExportSync $productSync,
        protected AkeneoConnector $akeneoConnector,
        protected KitPersonalizacionSportConnector $bcConnector,
        protected ManagerRegistry $managerRegistry,
        protected PricesFromBcSync $pricesFromBcSync,
        protected SendEmail $sendEmail,
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }



    protected function getProductsByBrand($brand)
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'IN', [$brand])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'platform_b2b');
    }

    
    public function synchronize()
    {
       
        $brandEnableds= $this->manager->getRepository(Brand::class)->findByEnabled(true);
        $newSkus = [];
        
        foreach($brandEnableds as $brandEnabled) {

            $this->logger->info('Get Products form '.$brandEnabled->getCode());
            $products= $this->getProductsByBrand($brandEnabled->getCode());
            foreach ($products as $product) {
                try {
    
                    $sku = $product['identifier'];
                   
    
                    $productDb = $this->manager->getRepository(Product::class)->findOneBySku($sku);
    
                    if(!$productDb) {

                        $itemBc = $this->bcConnector->getItemByNumber($sku);
                        if(!$itemBc) {
                            throw new Exception('Product '.$sku.'do no exists in BC');
                        }

                        $productDb=new Product();
                        $productDb->setSku($sku);
                        
                        $this->manager->persist($productDb);
                        $productDb->setBrand($brandEnabled);
                        $this->addInfoFromBc($productDb, $itemBc);
                        $this->manager->flush();
    
                        if($productDb->isEnabled()) {
                            $newSkus[] = $itemBc;
                        }
                    }
                    
                } catch (Exception $e) {
                    $errors [] = $e->getMessage();
                    $this->logger->critical('Error '.$e->getMessage());
                }
                 $this->manager->flush();
            }
        }
        


        if(count($newSkus)>0) {
            $text= count($newSkus).' products has been added to BigBuy app. You nedd to define specific prices. You can edit on <a href="https://bigbuy.kps-group.com/">https://bigbuy.kps-group.com/</a><br/><br/>';
            $text.='<table width="100%" cellpadding="0" cellspacing="0" style="min-width:100%;  border-collapse: collapse; border-style: solid; border-color: # aeabab;" border="1px">';
            foreach($newSkus as $newSku) {
                $text.='<tr><td style="padding:5px; border: 1px solid #aeabab;" align="left">'. $newSku['number'].'</td>';
                $text.='<td style="padding:5px; border: 1px solid #aeabab;" align="left">'. $newSku['displayName'].'</td></tr>';
            }
            $text.='</table>';
            $this->sendEmail->sendEmail(['eclos@kpsport.com', 'devops@kpsport.com'], 'New products on BigBuy', $text);
        }

        $this->logger->info('---------------------------------------------------------------');
        $this->logger->info('End synchro creation product with '.count($newSkus).' products on app');
        $this->logger->info('---------------------------------------------------------------');
    }


    protected function addInfoFromBc(Product $productDb, array $itemBc)
    {
        $productDb->setBundle($this->isBundle($itemBc));
        $productDb->setPublicPrice($this->getRetailPrice($productDb->getSku()));
        $productDb->setResellerPrice($this->getResellerPrice($productDb->getSku()));
        $productDb->setCostPrice($itemBc['unitCost']);
        $productDb->setCanonDigital($this->getCanonDigitalForItem($itemBc));
        $productDb->setStockLaRoca($this->pricesFromBcSync->getFinalStockProductWarehouse($productDb->getSku(), $productDb->isBundle()));

        $productDb->setVatCode($itemBc['vatProdPostingGroup']);
        $productDb->setNameErp($itemBc['displayName']);
            
        if($itemBc['itemStatus']=='Inactivo') {
            $productDb->setEnabled(false);
            $productDb->setActiveInBc(false);
        } else {
            $productDb->setActiveInBc(true);
        }
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


    public function getResellerPrice($sku)
    {
        $itemPricesGroup = $this->bcConnector->getPricesSkuPerGroup($sku, 'PVD-ES');
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

        return $bestPrice ? round($bestPrice, 2) : null;
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
