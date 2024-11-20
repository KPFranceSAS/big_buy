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

class ProductExportSync
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
        foreach ($brandEnableds as $brandEnabled) {

            $this->logger->info('Get Products form '.$brandEnabled->getCode());
            $products= $this->getProductsByBrand($brandEnabled->getCode());
            foreach ($products as $product) {
                $productDb = $this->manager->getRepository(Product::class)->findOneBySku($product['identifier']);

                if ($productDb && $productDb->isEnabled()) {
                    $productToArray = $this->flatProduct($product);
                    $productToArrays[]= $productToArray;
                }
            }
        }
        $this->sendProducts($productToArrays);
    }


    public function flatProduct(array $product):array
    {

        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'sku' => $product['identifier'],
            'ean' => $this->getAttributeSimple($product, 'ean'),
            'manufacturer_number' => $this->getAttributeSimple($product, 'manufacturer_number'),
            'erp_name' => $this->getAttributeSimple($product, 'erp_name'),
            'family' => $this->getFamilyName($product['family'], $this->getLocale()),
            'category' => $this->getCategoriePath($product['categories']),
            'parent' => $product['parent'],
            'title' => $this->getAttributeSimpleScopable($product, "article_name", 'Marketplace', $this->getLocale()),
            'brandName' => $this->getAttributeChoice($product, 'brand', $this->getLocale()),
            'description' =>  $this->getAttributeSimpleScopable($product, 'description', 'Marketplace', $this->getLocale())
        ];

        for ($i = 1; $i <= 5;$i++) {
            $imageLocale = $this->getAttributeSimple($product, 'image_url_loc_'.$i, $this->getLocale());
            $flatProduct['image_'.$i] =$imageLocale ? $imageLocale : $this->getAttributeSimple($product, 'image_url_'.$i);
        }


        $dimensionsToConvert = [
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


        foreach ($dimensionsToConvert as $fieldMirakl => $fieldPim) {
            $valueConverted = $this->getAttributeUnit($product, $fieldPim['field'], $fieldPim['unit'], $fieldPim['round']);
            $flatProduct[$fieldMirakl] = $valueConverted;
        }



        $flatProduct ['attributes'] = [];



        $multichoices = [
            "technology",
            "compatibility_connectivity",
            'cam_features',
            'activity_tracking',
            "screen_resolution",
            "screen_technology",
            "sound_technology",
            "sensors",
            "intelligent_technology",
            "smart_functions",
            "video_quality",
            "conexions"
        ];

        foreach ($multichoices as $multichoice) {
            $valueConverted = $this->getAttributeMultiChoice($product, $multichoice, $this->getLocale());
            if ($valueConverted) {
                $flatProduct ['attributes'][$multichoice] = $valueConverted;
            }
        }

        $choices = [
            "color",
            "size",
            "battery_type",
            "power_source_type",
            "processor",
            "energy_classification",
            "graphic_card",
            "chipset",
            "keyboard",
            "form_factor"
        ];

        foreach ($choices as $choice) {
            $valueConverted = $this->getAttributeChoice($product, $choice, $this->getLocale());
            if ($valueConverted) {
                $flatProduct ['attributes'][$choice] = $valueConverted;
            }
        }


        $units = [
            [
                "field" => 'internal_storage_memory',
                "unit" => 'giga_octet',
                "convertUnit" => 'Go' ,
                'round' => 0
            ],
            [
                "field" => 'hard_drive_capacity',
                "unit" => 'giga_octet',
                "convertUnit" => 'Go' ,
                'round' => 0
            ],

            
            [
                "field" => 'battery_power',
                "unit" => 'MILLIAMPEREHOUR',
                "convertUnit" => 'mAH' ,
                'round' => 0
            ],

            [
                "field" => 'battery_capacity_wh',
                "unit" => 'WATTHOUR',
                "convertUnit" => 'Wh' ,
                'round' => 0
            ],
            [
                "field" => 'autonomy',
                "unit" => 'MINUTE',
                "convertUnit" => 'min' ,
                'round' => 0
            ],
            [
                "field" => 'brightness',
                "unit" => 'LUMEN',
                "convertUnit" => 'lm' ,
                'round' => 0
            ],
            [
                "field" => 'output_power',
                "unit" => 'AMPERE',
                "convertUnit" => 'A' ,
                'round' => 0
            ],
            [
                "field" => 'ram_memory',
                "unit" => 'giga_octet',
                "convertUnit" => 'Go' ,
                'round' => 0
            ],
            [
                "field" => 'capacity_cooking',
                "unit" => 'LITER',
                "convertUnit" => 'L' ,
                'round' => 0
            ],
            [
                "field" => 'power',
                "unit" => 'WATT',
                "convertUnit" => 'W' ,
                'round' => 0
            ],

            [
                "field" => 'cooling_capacity',
                "unit" => 'WATT',
                "convertUnit" => 'W' ,
                'round' => 0
            ],

            [
                "field" => 'processor_speed',
                "unit" => 'GIGAHERTZ',
                "convertUnit" => 'GHz' ,
                'round' => 2
            ],

            [
                "field" => 'screen_size',
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 2
            ],

            [
                "field" => 'cable_length',
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],

            [
                "field" => 'screen_resolution_pixel',
                "unit" => 'GIGAPIXEL',
                "convertUnit" => 'Gpx' ,
                'round' => 2
            ],
            [
                "field" => 'camera_resolution',
                "unit" => 'GIGAPIXEL',
                "convertUnit" => 'Gpx' ,
                'round' => 2
            ],
            


            
            

            
         ];

        foreach ($units as $fieldPim) {
            $valueConverted = $this->getAttributeUnit($product, $fieldPim['field'], $fieldPim['unit'], $fieldPim['round']);
            if ($valueConverted) {
                $valueConverted = $valueConverted.' '.$fieldPim['convertUnit'];
                $flatProduct ['attributes'][$fieldPim['field']] = $valueConverted;
            }
        }

        return $flatProduct;
    }



    public function getLocale()
    {
        return 'es_ES';
    }

    
    protected function getProductsByBrand($brand)
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'IN', [$brand])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder);
    }



    public function sendProducts(array $products)
    {
        $this->logger->info("start export ".count($products)." products");
        $jsonCOntent = json_encode($products);
        $filename = 'export_products_sftp_'.date('Ymd_His').'.json';
        $this->logger->info("start export products locally");
        $this->defaultStorage->write('products/'.$filename, $jsonCOntent);
        $this->logger->info("start export products on big buy");
        $this->bigBuyStorage->write('Products/products.json', $jsonCOntent);
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
        if (!array_key_exists($attributeCode.'_'.$code, $this->attributes)) {
            $this->attributes[$attributeCode.'_'.$code] = $this->akeneoConnector->getAttributeOption($attributeCode, $code);
        }
        return array_key_exists($locale, $this->attributes[$attributeCode.'_'.$code]['labels']) ? $this->attributes[$attributeCode.'_'.$code]['labels'][$locale] : $code;
    }


    protected $families = [];

    protected function getFamilyName($identifier, $langage)
    {
        if (!array_key_exists($identifier, $this->families)) {
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
            "SQUARE_INCH" => 0.00064516,

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


            "HERTZ" => 1,
            "KILOHERTZ" => 1000,
            "MEGAHERTZ" => 1000000,
            "GIGAHERTZ" => 1000000000,
            "TERAHERTZ" => 1000000000000,


            "PIXEL" => 1,
            "MEGAPIXEL" => 1000000,
            "GIGAPIXEL" => 1000000000,

            "MILLISECOND" => 0.001,
            "SECOND" => 1,
            "MINUTE" => 60,
            "HOUR" => 3600,
            "DAY" => 86400,
            "WEEK" => 604800,
            "MONTH" => 2628000,
            "YEAR" => 31536000,

            "LUMEN" => 1,
            "NIT" => 0.2918855809
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


    private $categories;

    protected function getAllCategories()
    {
        $this->categories=[];
        $categoriePims = $this->akeneoConnector->getAllCategories();
        foreach ($categoriePims as $category) {
            $this->categories[ $category['code']] = $category;
        }
    }



    protected function getCategoriePath(array $categories)
    {
        if (!$this->categories) {
            $this->getAllCategories();
        }
        $deepestCategories = null;
        foreach ($categories as $categorie) {
            $access = $this->getAccessCategories($categorie);
            $lastLevel = end($access);
            if ($lastLevel && $lastLevel['code']=='kps_tech') {
                if (!$deepestCategories || count($deepestCategories) < count($access)) {
                    $deepestCategories = $access;
                }
            }
            
        }

        if ($deepestCategories) {
            $paths = [];
            foreach ($deepestCategories as $deepestCategorie) {
                if ($deepestCategorie['code']!='kps_tech') {
                    $paths[]= $deepestCategorie['labels']['es_ES'];
                }
                
            }
            $pathReversed = array_reverse($paths);

            return implode(" > ", $pathReversed);
        } else {
            return null;
        }
    }


    protected function getAccessCategories($slug)
    {
        $access = [];
        $continue =true;
        while ($continue) {
            $categoryPim = $this->categories[$slug];
            $access[]= $categoryPim;
            if ($categoryPim['parent']) {
                $slug = $categoryPim['parent'];
            } else {
                $continue = false;
            }
        }
        return $access;
    }




}
