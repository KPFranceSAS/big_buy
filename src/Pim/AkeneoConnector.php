<?php

namespace App\Pim;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Exception;
use Psr\Log\LoggerInterface;

class AkeneoConnector
{
    private $client;

    public function __construct(
        private LoggerInterface $logger,
        string $akeneoUrl,
        string $akeneoClientId,
        string $akeneoClientSecret,
        string $akeneoUsername,
        string $akeneoPassword
    ) {
        $clientBuilder = new AkeneoPimClientBuilder($akeneoUrl);
        $this->client = $clientBuilder->buildAuthenticatedByPassword(
            $akeneoClientId,
            $akeneoClientSecret,
            $akeneoUsername,
            $akeneoPassword
        );
    }

    public function getFamily($family)
    {
        return $this->client->getFamilyApi()->get($family);
    }

    public function getFamilyVariant($family, $familyVariant)
    {
        return $this->client->getFamilyVariantApi()->get($family, $familyVariant);
    }


    public function getAttribute($code)
    {
        return $this->client->getAttributeApi()->get($code);
    }


    public function getAttributeOption($attributeCode, $code)
    {
        return $this->client->getAttributeOptionApi()->get($attributeCode, $code);
    }

    
    public function getProductModel($productModel)
    {
        return $this->client->getProductModelApi()->get($productModel);
    }


    public function getAllProducts()
    {
        return $this->client->getProductApi()->all();
    }


  

    public function searchProducts(SearchBuilder $searchBuilder, $locale=null, $scope =null)
    {
        $searchFilters = $searchBuilder->getFilters();
        $params = ['search' => $searchFilters];
        if($scope) {
            $params['scope'] = $scope;
        }

        if($locale) {
            $params['locale'] = $locale;
        }


        return $this->client->getProductApi()->all(50, $params);
    }



    public function getAllFiltreredProducts(SearchBuilder $searchFilters)
    {
        return $this->client->getProductApi()->all('50', ['search'=>$searchFilters->getFilters()]);
    }

    public function updateProduct($identifier, $values)
    {
        return $this->client->getProductApi()->upsert($identifier, $values);
    }



    public function getAllCategories()
    {
        return $this->client->getCategoryApi()->all();
    }


    public function getAllChildrenCategoriesByParent($parentCode)
    {
        $searchFilters = new SearchBuilder();
        $searchFilters->addFilter('parent', '=', $parentCode);
        
        return $this->client->getCategoryApi()->all('50', ['search'=>$searchFilters->getFilters()]);
    }



    public function getClient()
    {
        return $this->client;
    }
}
