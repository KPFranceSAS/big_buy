<?php

namespace App\Synchronization\Product;

use App\Entity\Product;
use App\Synchronization\Product\ProductCreationFromBcSync;
use Exception;

class ProductUpdateFromBcSync extends ProductCreationFromBcSync
{
    
    
    
    public function synchronize()
    {
        $products = $this->manager->getRepository(Product::class)->findAll();

        foreach ($products as $product) {
            try {
                $itemBc = $this->bcConnector->getItemByNumber($product->getSku());
                $this->addInfoFromBc($product, $itemBc);

                
            } catch (Exception $e) {
                $errors [] = $e->getMessage();
                $this->logger->critical('Error '.$e->getMessage());
            }

            
        }
        $this->manager->flush();
        $this->logger->info('---------------------------------------------------------------');
        $this->logger->info('End synchro update product with ');
        $this->logger->info('---------------------------------------------------------------');
    }


}
