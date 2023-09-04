<?php

namespace App\Synchronization\Product;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Mailer\SendEmail;
use App\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class ProductExportSyncToCsv
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
        /** @var  array $products */
        $products = $this->getProductsEnabledOnChannel();
        $productToArrays=[];
        $base = ['sku','brandName','ean','erp_name', 'parent','color', 'size', 'family','length', "width" , "height" , "weight",'package_length', "package_width" , "package_height" , "package_weight", "title", "description" ];
        $header = [];
        foreach ($products as $product) {
            $productToArray = $this->flatProduct($product);
            $headerProduct = array_keys($productToArray);
            foreach ($headerProduct as $headerP) {
                if (!in_array($headerP, $header) && !in_array($headerP, $base)) {
                    $header[] = $headerP;
                }
            }
            $productToArrays[]= $productToArray;
        }
        sort($header);
        $finalHeader = array_merge($base, $header);
        $this->sendProducts($productToArrays, $finalHeader);
    }


    public function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'sku' => $product['identifier'],
            'ean' => $this->getAttributeSimple($product, 'ean'),
            'erp_name' => $this->getAttributeSimple($product, 'erp_name'),
            'family' => $this->getFamilyName($product['family'], $this->getLocale()),
            'categories' => implode(',', $product['categories']),
            'parent' => $product['parent'],
        ];

        for ($i = 1; $i <= 5;$i++) {
            $imageLocale = $this->getAttributeSimple($product, 'image_url_loc_'.$i, $this->getLocale());
            $flatProduct['image_'.$i] =$imageLocale ? $imageLocale : $this->getAttributeSimple($product, 'image_url_'.$i);
        }

        $flatProduct['title'] = $this->getAttributeSimple($product, "article_name", $this->getLocale());
        
        $descriptionFinal = $this->getAttributeSimple($product, 'description', $this->getLocale());
        $flatProduct['description'] = $descriptionFinal ?  $this->removeNewLine($descriptionFinal) : '';

        $valueGarantee =  $this->getAttributeChoice($product, 'manufacturer_guarantee', $this->getLocale());
        if ($valueGarantee) {
            $flatProduct['warranty'] = (int)$valueGarantee.' years';
        }

        $fieldsToConvert = [
            "brandName" => [
                "field" => "brand",
                "type" => "choice",
            ],
            "color" => [
                "field" => "color",
                "type" => "choice",
            ],
            "size" => [
                "field" => "size",
                "type" => "choice",
            ],
            "internal_storage_memory" => [
                "field" => 'internal_storage_memory',
                "type" => "unit",
                "unit" => 'giga_octet',
                "convertUnit" => 'Go' ,
                'round' => 0
            ],
            "length" => [
                "field" => 'product_lenght',
                "type" => "unit",
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "width" => [
                "field" => 'product_width',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "height" => [
                "field" => 'product_height',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm',
                'round' => 0
            ],
            "weight" => [
                "field" => 'product_weight',
                "unit" => 'KILOGRAM',
                "type" => "unit",
                "convertUnit" => 'kg',
                'round' => 2
            ],
            "package_length" => [
                "field" => 'package_lenght',
                "type" => "unit",
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "package_width" => [
                "field" => 'package_width',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "package_height" => [
                "field" => 'package_height',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm',
                'round' => 0
            ],
            "package_weight" => [
                "field" => 'package_weight',
                "unit" => 'KILOGRAM',
                "type" => "unit",
                "convertUnit" => 'kg',
                'round' => 2
            ],
         ];

        foreach ($fieldsToConvert as $fieldMirakl => $fieldPim) {
            if ($fieldPim['type']=='unit') {
                $valueConverted = $this->getAttributeUnit($product, $fieldPim['field'], $fieldPim['unit'], $fieldPim['round']);
                $flatProduct[$fieldMirakl] = $valueConverted;
            } elseif ($fieldPim['type']=='choice') {
                $flatProduct[$fieldMirakl] = $this->getAttributeChoice($product, $fieldPim['field'], $this->getLocale());
            }
        }
        return $flatProduct;
    }



    public function getLocale()
    {
        return 'es_ES';
    }

    



    public function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'IN', ['xiaomi', 'aiper', 'victrola', 'roborock', 'xgimi'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }



    public function sendProducts(array $products, $header)
    {
        $csv = Writer::createFromString();
        $csv->setDelimiter(';');
        $csv->insertOne($header);
        $this->logger->info("start export ".count($products)." products");
        foreach ($products as $product) {
            $productArray = $this->addProduct($product, $header);
            $csv->insertOne(array_values($productArray));
        }
        $csvContent = $csv->toString();
        $filename = 'export_products_sftp_'.date('Ymd_His').'.csv';
        $this->logger->info("start export products locally");
        $this->defaultStorage->write('products/'.$filename, $csvContent);
        $this->logger->info("start export products on big buy");
        $this->bigBuyStorage->write('products/products.csv', $csvContent);
    }


    private function addProduct(array $product, array $header): array
    {
        $productArray = array_fill_keys($header, '');
        
        foreach ($header as $column) {
            if (array_key_exists($column, $product)) {
                $productArray[$column]=$product[$column];
            }
        }

        return $productArray;
    }




    protected function getAxesVariation($family, $familyVariant): array
    {
        $familyVariant = $this->akeneoConnector->getFamilyVariant($family, $familyVariant);
        return $this->getAxes($familyVariant);
    }
    

    protected function getAxes(array $variantFamily): array
    {
        $axes = [];
        foreach ($variantFamily['variant_attribute_sets'] as $variantAttribute) {
            foreach ($variantAttribute['axes'] as $axe) {
                $axes[]= $axe;
            }
        }
        if ($this->getNbLevels()==1 && count($axes)==2) {
            unset($axes[0]);
            $axes= array_values($axes);
        }

        return $axes;
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




    protected function getAttributeSimpleScopable($productPim, $nameAttribute, $scope, $locale=null)
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




    protected function getTranslationLabel($nameAttribute, $locale)
    {
        $attribute = $this->akeneoConnector->getAttribute($nameAttribute);
        return array_key_exists($locale, $attribute['labels']) ? $attribute['labels'][$locale] : $nameAttribute;
    }


    private $attributes = [];

   

    protected function getTranslationOption($attributeCode, $code, $locale)
    {
        if(!array_key_exists($attributeCode.'_'.$code, $this->attributes)) {
            $this->attributes[$attributeCode.'_'.$code] = $this->akeneoConnector->getAttributeOption($attributeCode, $code);
        }
        return array_key_exists($locale, $this->attributes[$attributeCode.'_'.$code]['labels']) ? $this->attributes[$attributeCode.'_'.$code]['labels'][$locale] : $code;
    }


    protected $families = [];

    protected function getFamilyName($identifier, $langage)
    {
        if(!array_key_exists($identifier, $this->families)) {
            $this->families[$identifier] = $this->akeneoConnector->getFamily($identifier);
        }
        return array_key_exists($langage, $this->families[$identifier]['labels']) ? $this->families[$identifier]['labels'][$langage] : $identifier;
    }

    protected function getTitle($productPim, $locale, $isModel=false)
    {
        if ($isModel) {
            $parentTitle = $this->getAttributeSimple($productPim, 'parent_name', $locale);
            if ($parentTitle) {
                return $parentTitle;
            }
        }

        $title = $this->getAttributeSimple($productPim, 'article_name', $locale);
        if ($title) {
            return $title;
        }

        $titleDefault = $this->getAttributeSimple($productPim, 'article_name_defaut', $locale);
        if ($titleDefault) {
            return $titleDefault;
        }

        return $this->getAttributeSimple($productPim, 'erp_name');
    }


    protected function getAttributePrice($productPim, $nameAttribute, $currency)
    {
        $valueAttribute = $this->getAttributeSimple($productPim, $nameAttribute);
        if ($valueAttribute) {
            foreach ($valueAttribute as $value) {
                if ($value['currency']==$currency) {
                    return $value["amount"];
                }
            }
        }

        return null;
    }

    protected function getAttributeChoice($productPim, $nameAttribute, $locale)
    {
        $value = $this->getAttributeSimple($productPim, $nameAttribute);
        if ($value) {
            return $this->getTranslationOption($nameAttribute, $value, $locale);
        }
        return null;
    }


    protected function getAttributeMultiChoice($productPim, $nameAttribute, $locale)
    {
        $values = $this->getAttributeSimple($productPim, $nameAttribute);
        if ($values && is_array($values) && count($values)>0) {
            $valuesPim = [];
            foreach ($values as $value) {
                $valuesPim[]=$this->getTranslationOption($nameAttribute, $value, $locale);
            }
            return $valuesPim;
        }
        return [];
    }





    protected function getParentProduct($productModelSku)
    {
        $parent = $this->akeneoConnector->getProductModel($productModelSku);
        if ($this->getNbLevels()==1) {
            return $parent;
        } else {
            return $parent['parent'] ? $this->akeneoConnector->getProductModel($parent['parent']) : $parent;
        }
    }

    

    protected function getNbLevels()
    {
        return 2;
    }


    protected function getAttributeUnit($productPim, $nameAttribute, $unitToConvert, $nbRound)
    {
        if (array_key_exists($nameAttribute, $productPim['values'])) {
            $valueAttribute = $productPim['values'][$nameAttribute][0]['data'];
            return $valueAttribute['amount'] > 0 ? $this->transformUnit($valueAttribute["unit"], $unitToConvert, $valueAttribute['amount'], $nbRound) : 0;
        }
        return null;
    }



    protected function transformUnit($unitBase, $unitFinal, $value, $nbRound)
    {
        $factors = [
            "SQUARE_METER" =>1,
            "SQUARE_CENTIMETER" =>0.0001,
            "SQUARE_MILLIMETER" =>0.000001,
            "SQUARE_KILOMETER" =>1000000,

            "KILOMETER" => 1000.0,
            "METER" => 1.0,
            "DECIMETER" => 0.1,
            "CENTIMETER" => 0.01,
            "MILLIMETER" => 0.001,

            "MILLILITER" => 0.000001,
            "CENTILITER" => 0.00001,
            "LITER" => 0.001,
            "CUBIC_MILLIMETER" => 0.000000001,
            "CUBIC_CENTIMETER" => 0.000001,
            "CUBIC_DECIMETER" => 0.001,
            "CUBIC_METER" => 1.0,

            "giga_octet" => 1.0,

            "TON" => 1000.0,
            "KILOGRAM" => 1.0,
            "GRAM" => 0.001,
            "MILLIGRAM" => 0.000001,

            "MILLIAMPEREHOUR" => 0.001,
            "AMPEREHOUR" => 1,

            "MILLIAMPERE" => 0.001,
            "CENTIAMPERE" => 0.01,
            "DECIAMPERE" => 0.1,
            "AMPERE" => 1,
            
            "WATTHOUR" => 1,
            "MILLIWATTHOUR" => 0.001,

            "WATT_CRETE" => 1,
            "KILLOWATT_CRETE" => 1000,

            "WATT" => 1,
            "KILOWATT" => 1000,
            "MEGAWATT" => 1000000,


        ];

        if (!array_key_exists($unitBase, $factors) || !array_key_exists($unitFinal, $factors)) {
            $this->logger->critical("Invalid units ".$unitBase." or ".$unitFinal);
            return 0;
        }
        $valueBase = $value * $factors[$unitBase];
        return round($valueBase / $factors[$unitFinal], $nbRound);
    }




    protected function isMetric($val)
    {
        return is_array($val) && array_key_exists("unit", $val);
    }

    protected function isCurrency($val)
    {
        return is_array($val) && is_array($val[0]);
    }


    protected function getAttributeColumnName($attribute, $val)
    {
        $nameAttribute=$attribute;
        if ($val['locale']) {
            $nameAttribute .= '-'. $val['locale'];
        }
        if ($val['scope']) {
            $nameAttribute .= '-'. $val['scope'];
        }
        return $nameAttribute;
    }
    



    protected function getDescription($productPim, $locale)
    {
        $description = $this->getAttributeSimple($productPim, 'description', $locale);
        if ($description) {
            return $description;
        }

        $decriptionDefault = $this->getAttributeSimple($productPim, 'description_defaut', $locale);
        if ($decriptionDefault) {
            return '<p>'.$decriptionDefault.'</p>';
        }

        return null;
    }

    protected function removeNewLine(string $text): string
    {
        return str_replace(["\r\n", "\n"], '', $text);
    }


    protected function sanitizeHtml(string $text): string
    {
        return $this->removeNewLine(strip_tags($text));
    }
}
