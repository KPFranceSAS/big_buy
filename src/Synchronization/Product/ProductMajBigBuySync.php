<?php

namespace App\Synchronization\Product;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Brand;
use App\Entity\Product;
use App\Mailer\SendEmail;
use App\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class ProductMajBigBuySync
{


    private $manager;

    public function __construct(
        private LoggerInterface $logger,
        private AkeneoConnector $akeneoConnector,
        private ManagerRegistry $managerRegistry,
        private KitPersonalizacionSportConnector $bcConnector,
        private FilesystemOperator $defaultStorage,
        private FilesystemOperator $bigBuyStorage,
        private SendEmail $sendEmail
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }

    
    public function synchronize()
    {
        $productToArrays=[];
        $brandEnableds= $this->manager->getRepository(Brand::class)->findByEnabled(true);
        foreach($brandEnableds as $brandEnabled) {

            $this->logger->info('Get Products form '.$brandEnabled->getCode());
            $products= $this->getProductsByBrand($brandEnabled->getCode());
            foreach ($products as $product) {
                $productDb = $this->manager->getRepository(Product::class)->findOneBySku($product['identifier']);

                if($productDb && $productDb->isEnabled()) {
                    $updatePim = [];
                    $productEnabledOnMarketPlace = $this->getAttributeSimpleScopable($product, 'enabled_channel', 'Marketplace');
                    if($productEnabledOnMarketPlace!=true) {
                        $updatePim['enabled_channel']=[
                            [
                                'data' => true,
                                'scope'=>  'Marketplace',
                                'locale' => null
                            ]
                            ];
                    }

                    $productAssignation = $this->getAttributeSimpleScopable($product, 'marketplaces_assignement');

                    if(!$productAssignation || ($productAssignation && !in_array('bigbuy_es_kps', $productAssignation))) {
                        if(!$productAssignation) {
                            $productAssignation=[];
                        }
                        $productAssignation[]='bigbuy_es_kps';
                        $updatePim['marketplaces_assignement']=[
                            [
                                'data' => $productAssignation,
                                'scope'=>  null,
                                'locale' => null
                            ]
                            ];
                    }

                    if(count($updatePim)>0) {
                        $this->akeneoConnector->updateProductParent($product['identifier'], $product['parent'], $updatePim);
                    }
                }
            }
        }
        
    }







    protected function getAttributeSimpleScopable($productPim, $nameAttribute, $scope=null, $locale=null)
    {
        if (array_key_exists($nameAttribute, $productPim['values'])) {
            if ($locale) {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                   
                    if ($attribute['locale']==$locale && $attribute['scope']==$scope) {
                        return $attribute['data'];
                    }
                }
            } else {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                   
                    if ($attribute['scope']==$scope) {
                        return $attribute['data'];
                    }
                }
            }
        }
        return null;
    }


   

    
    protected function getProductsByBrand($brand)
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'IN', [$brand])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder);
    }

}
