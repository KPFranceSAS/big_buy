<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Model\SaleOrderBc;
use App\BusinessCentral\Model\SaleOrderLineBc;
use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Entity\SaleOrderLine;
use App\Helper\Utils\CalculatorNext;
use App\Mailer\SendEmail;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;

class OrdersCreation
{

    public const CUSTOMER_NUMBER="KP131503";


    private $manager;

    private $errors;

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $managerRegistry,
        private KitPersonalizacionSportConnector $bcConnector,
        private SendEmail $sendEmail,
        private FilesystemOperator $bigBuyStorage,
        private FilesystemOperator $defaultStorage,
        private string $closingHours
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }
    
    public function synchronize()
    {
        $this->errors = [];
        $nbIntegrated = 0;
        $listFiles = $this->bigBuyStorage->listContents('/Orders', false);
        /** @var \League\Flysystem\StorageAttributes $listFile */
        foreach($listFiles as $listFile) {
            if($listFile->isFile()) {
                $nbIntegrated += (int)$this->integrateFile($listFile);
            }
        }

        if(count($this->errors)>0) {
            $this->sendEmail->sendEmail(['devops@kpsport.com'], 'Order errors', implode('<br/>', $this->errors));
        }

        $this->logger->info($nbIntegrated.' orders integrated');
    }


    protected function integrateFile(StorageAttributes $listFile)
    {
        $this->logger->info('Integrate file >>>'.$listFile->path());
        
        $saleLinesArrayToIntegrate = [];
        $saleLinesArray = $this->extractContent($listFile->path());
        $this->logger->info('Sale lines in array  >>>'.count($saleLinesArray));
        $errorOrder=false;
        foreach($saleLinesArray as $k => $saleLineArray) {
            $error = $this->checkLine($saleLineArray);
            if($error) {
                $errorOrder = true;
                $saleLinesArray[$k]['error'] = $error;
            } else {
                $saleLinesArrayToIntegrate[]=$saleLineArray;
            }
        }
        
        try {
            $dateRelease =  CalculatorNext::getNextDelivery(new DateTime('now'), $this->closingHours);
                
            $this->logger->info('Release date '. $dateRelease->format('Y-m-d H:i'));
            $saleOrder = $this->getSaleOrder($dateRelease);
            $saleOrderBc = $this->bcConnector->getSaleOrderByNumber($saleOrder->getOrderNumber());
            foreach($saleLinesArrayToIntegrate as $k => $saleLineArray) {
                $this->addSaleOrderLine($saleOrder, $saleOrderBc, $saleLineArray);
            }
        } catch (Exception $e) {
            $this->sendEmail->sendAlert('Error', $e->getMessage());
        }

        if($errorOrder) {
            $this->manageErrorOrders($listFile, $saleLinesArray);
        } else {
            $saleOrder->addLog('Copy locally file '.$listFile->path().'in success');
            $this->defaultStorage->write(str_replace('Orders/', 'Orders/Success/', $listFile->path()), $this->bigBuyStorage->read($listFile->path()));
            $saleOrder->addLog('Move on Big Buy file '.$listFile->path().'in Processed ');
            $this->bigBuyStorage->move($listFile->path(), str_replace('Orders/', 'Orders/Processed/', $listFile->path()));
        }
        return count($saleLinesArrayToIntegrate)>0;
    }


    protected function addSaleOrderLine(SaleOrder $saleOrder, array $saleOrderBc, array $orderBigBuy)
    {
        $priceBigBuy = floatval($orderBigBuy['price']);
        $saleOrderLineBc = new SaleOrderLineBc();
        $itemBc = $this->bcConnector->getItemByNumber($orderBigBuy['sku']);
        $saleOrderLineBc->itemId = $itemBc['id'];
        $saleOrderLineBc->quantity = (int)$orderBigBuy['quantity'];
        $saleOrderLineBc->lineType = "Item";
        $saleOrderLineBc->unitPrice =$priceBigBuy;

        $saleOrderLineBcCreated = $this->bcConnector->createSaleOrderLine($saleOrderBc['id'], $saleOrderLineBc->transformToArray());
        $saleOrder->addLog('Created sale order line in BC '. json_encode($saleOrderLineBc->transformToArray()));


        $reservation = [
            "QuantityBase" => (int)$orderBigBuy['quantity'],
            "CreationDate" => date('Y-m-d'),
            "ItemNo" => $orderBigBuy['sku'],
            "LocationCode" =>  $saleOrderBc['locationCode'],
            "SourceID" => $saleOrder->getOrderNumber(),
            "SourceRefNo"=> $saleOrderLineBcCreated['sequence'],
        ];

        $this->bcConnector->createReservation($reservation);
        $saleOrder->addLog('Add reservation for line '.$saleOrderLineBcCreated['sequence'].' for '.$saleOrderLineBcCreated['quantity'].' '.$saleOrderLineBcCreated['lineDetails']['number']);

        $saleOrder->addLog('Created sale order line in BC '. json_encode($saleOrderLineBc->transformToArray()));


        $saleOrderLine = new SaleOrderLine();
        $saleOrder->addSaleOrderLine($saleOrderLine);
        $saleOrderLine->setQuantity($orderBigBuy['quantity']);
        $saleOrderLine->setSku($orderBigBuy['sku']);
        $saleOrderLine->setLineNumber($saleOrderLineBcCreated['sequence']);
        $saleOrderLine->setPrice($priceBigBuy);
        $saleOrderLine->setBigBuyOrderLine($orderBigBuy['order_id']);
            
            
        $this->manager->persist($saleOrderLine);
        $this->manager->flush();
    }


    protected function getSaleOrder(DateTime $dateTime): SaleOrder
    {
        $saleOrder = $this->manager->getRepository(SaleOrder::class)->findOneBy(['releaseDateString'=>$dateTime->format('Y-m-d H:i')]);
        if(!$saleOrder) {
            $saleOrderBc = new SaleOrderBc();
            $saleOrderBc->customerNumber = self::CUSTOMER_NUMBER;
            $saleOrderBc->shippingAgent = "DHL PARCEL";
            $saleOrderBc->shippingAgentService = 'DHL1';
            $saleOrderBc->externalDocumentNumber ='RELEASE '.$dateTime->format('d-m-Y H:i');
            $saleOrderBc->shipToName =' ALL 4 BUSINESS, SL';
            $saleOrderBc->shippingPostalAddress->street ='Carrer Quinsà, 12';
            $saleOrderBc->shippingPostalAddress->city ='Moncada';
            $saleOrderBc->shippingPostalAddress->postalCode ='46113';
            $saleOrderBc->shippingPostalAddress->countryLetterCode ='ES';
            $saleOrderBc->shippingPostalAddress->state ='Valencia';
           
            $saleOrderBcCreated = $this->bcConnector->createSaleOrder($saleOrderBc->transformToArray());
            
            $saleOrder = new SaleOrder();
            $saleOrder->setStatus(SaleOrder::STATUS_OPEN);
            $saleOrder->setOrderNumber($saleOrderBcCreated['number']);
            $saleOrder->addLog('Create sale order in BC '.$saleOrderBcCreated['number']);
            $saleOrder->setReleaseDate($dateTime);
            $this->manager->persist($saleOrder);
            $this->manager->flush();
        }
        return $saleOrder;
    }




    protected function manageErrorOrders(StorageAttributes $listFile, array $errors)
    {
        $lines = [['order_id', 'sku', 'quantity','price', 'error']];
        $this->errors[] = 'Order '.$errors[0]['id'].' cannot be integrated in our system';
        foreach($errors as $error) {
            $line = [
                $error['id'],
                $error['sku'],
                $error['quantity'],
                $error['price'],
            ];
            if(array_key_exists('error', $error)) {
                $this->errors[] ='For requested SKU '.$error['sku']. ' with qty '.$error['quantity'].' >>> error '.$error['error'];
                $line[] = $error['error'];
            } else {
                $line[] = null;
            }
            $lines[] = $line;
        }

        $csv = Writer::createFromString();
        $csv->setDelimiter(';');
        $csv->insertAll($lines);
        $newName = str_replace('Orders/', 'Orders/Error/', $listFile->path());
        $this->bigBuyStorage->write($newName, $csv->toString());
        $this->defaultStorage->write($newName, $csv->toString());
        $this->bigBuyStorage->delete($listFile->path());
        $this->errors[] = '----------------------------------------------';
        return false;

    }



    protected function checkOrder(array $saleLinesArray)
    {
       
        $errors = [];
        foreach($saleLinesArray as $k => $saleLineArray) {
            $error = $this->checkLine($saleLineArray);
            if($error) {
                $nvError = $saleLineArray;
                $nvError['error'] = $error;
                $errors[] = $nvError;
            }
        }
        return $errors;
    }



    protected function checkLine(array $saleLine)
    {
        // check if sku exists
        $itemBc = $this->bcConnector->getItemByNumber($saleLine['sku']);
        if(!$itemBc) {
            return 'SKU unknown '.$saleLine['sku'];
        }

        $product = $this->manager->getRepository(Product::class)->findOneBy(['sku'=>$saleLine['sku']]);
        if(!$product) {
            return 'Sku with no correlation '.$saleLine['sku'];
        }

        /* if(!$product->getPrice()) {
             return 'Sku with no selling price '.$saleLine['sku'];
         }*/

        // check availability
        $stockAvailability = $this->bcConnector->getStockAvailabilityPerProduct($saleLine['sku']);
        if(!$stockAvailability) {
            return 'No stock '.$saleLine['sku'];
        }

        if($stockAvailability['quantityAvailableLAROCA'] < $saleLine['quantity']) {
            return 'No enough stock '.$saleLine['sku'].' >> '.$stockAvailability['quantityAvailableLAROCA'].' available for '.$saleLine['quantity'].' requested' ;
        }

        return null;

    }


    protected function extractContent($path): array
    {
        $response = $this->bigBuyStorage->read($path);
        $datas = [];
        $contentArray =  explode("\n", $response);
        $header = explode(";", array_shift($contentArray));
        foreach ($contentArray as $contentLine) {
            $values = explode(";", $contentLine);
            if (count($values) == count($header)) {
                $datas[] = array_combine($header, $values);
            }
        }
        return $datas;
        
    }

}
