<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Model\SaleOrderBc;
use App\BusinessCentral\Model\SaleOrderLineBc;
use App\Entity\FileIntegrated;
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
                try {
                    $nbIntegrated += (int)$this->integrateFile($listFile);
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                    $this->logger->critical($e->getMessage());
                }
                
            }
        }

        if(count($this->errors)>0) {
            $this->sendEmail->sendEmail(['devops@kpsport.com'], '[KPS] Cancelación de pedidos', implode('<br/>', $this->errors));
        }

        $this->logger->info($nbIntegrated.' orders integrated');
    }


    protected function integrateFile(StorageAttributes $listFile)
    {
        $this->logger->info('Integrate file >>>'.$listFile->path());

        $fileIntegrated = $this->manager->getRepository(FileIntegrated::class)->findOneBy(['filename'=>str_replace('Orders/', '', $listFile->path())]);
        if($fileIntegrated) {
            $this->logger->info('File '.$listFile->path().' already integrated');
            $this->bigBuyStorage->delete($listFile->path());
            return 0;
        }

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

            
       
            if($errorOrder) {
                $this->manageErrorOrders($listFile, $saleLinesArray);
            } else {
                $saleOrder->addLog('Copy locally file '.$listFile->path().' in success');
                $this->defaultStorage->write(str_replace('Orders/', 'Orders/Success/', $listFile->path()), $this->bigBuyStorage->read($listFile->path()));
                $saleOrder->addLog('Move on Big Buy file '.$listFile->path().' in Processed ');
                $this->bigBuyStorage->move($listFile->path(), str_replace('Orders/', 'Orders/Processed/', $listFile->path()));
            }
            $fileTreated = new FileIntegrated();
            $fileTreated->setFilename(str_replace('Orders/', '', $listFile->path()));
            $this->manager->persist($fileTreated);
            
            
        } catch (Exception $e) {
            $message = mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");

            $this->sendEmail->sendAlert('Error OrdersCreation', $message.'<br/>'.$e->getTraceAsString());
            $saleOrder->addError('Error '.$message);
        }
        $this->manager->flush();
        return count($saleLinesArrayToIntegrate)>0;
    }


    protected function addSaleOrderLine(SaleOrder $saleOrder, array $saleOrderBc, array $orderBigBuy): int
    {

        $idOrderBigBuy = array_key_exists('order_id', $orderBigBuy) ? $orderBigBuy['order_id'] : $orderBigBuy['id'];

        $saleOrderLineBc = $this->manager->getRepository(SaleOrderLine::class)->findOneBy([
            'bigBuyOrderLine'=>$idOrderBigBuy,
            'sku'=>$orderBigBuy['sku']
        ]);

        if($saleOrderLineBc) {
            $this->logger->info('Already recorded in Business central');
            return 0;
        }


        $priceBigBuy = floatval($orderBigBuy['price']);
        $product = $this->manager->getRepository(Product::class)->findOneBy(['sku'=>$orderBigBuy['sku']]);
        if($product->getCanonDigital()) {
            
            $priceBigBuy = $priceBigBuy - $product->getCanonDigital();
            $saleOrder->addLog('Product '.$orderBigBuy['sku'].' has canon digital price should be  '.$priceBigBuy.' instead of '.floatval($orderBigBuy['price']));
        }


        $itemBc = $this->bcConnector->getItemByNumber($orderBigBuy['sku']);

        $lineNumber = $saleOrder->getLineSequenceForSkuPrice($orderBigBuy['sku'], $priceBigBuy);

        if($lineNumber) {
            $saleOrderLineToUpdate = $this->bcConnector->getSaleOrderLineByLineNumber($saleOrderBc['id'], $lineNumber);
            $nvQuantity = $saleOrderLineToUpdate['quantity'] + (int)$orderBigBuy['quantity'];
            $saleOrderLineBcCreated = $this->bcConnector->updateSaleOrderLine($saleOrderBc['id'], $saleOrderLineToUpdate['id'], '*', ['quantity'=>$nvQuantity]);
            $saleOrder->addLog('Updated sale order line in BC quantity > '.$saleOrderLineToUpdate['quantity'].' to '.$nvQuantity);
            $saleOrderLineBcCreated = $this->bcConnector->updateSaleOrderLine($saleOrderBc['id'], $saleOrderLineToUpdate['id'], '*', ['unitPrice'=> $priceBigBuy]);
            $saleOrder->addLog('Updated sale order line in BC price > '.$priceBigBuy);
        } else {
            $saleOrderLineBc = new SaleOrderLineBc();
            
            $saleOrderLineBc->itemId = $itemBc['id'];
            $saleOrderLineBc->quantity = (int)$orderBigBuy['quantity'];
            $saleOrderLineBc->lineType = "Item";
            $saleOrderLineBc->unitPrice =$priceBigBuy;
    
            $saleOrderLineBcCreated = $this->bcConnector->createSaleOrderLine($saleOrderBc['id'], $saleOrderLineBc->transformToArray());
            $saleOrder->addLog('Created sale order line in BC '. json_encode($saleOrderLineBc->transformToArray()));
            $lineNumber = $saleOrderLineBcCreated['sequence'];
        }


        $reservation = [
            "QuantityBase" => (int)$orderBigBuy['quantity'],
            "CreationDate" => date('Y-m-d'),
            "ItemNo" => $orderBigBuy['sku'],
            "LocationCode" =>  $saleOrderBc['locationCode'],
            "SourceID" => $saleOrder->getOrderNumber(),
            "SourceRefNo"=> $lineNumber,
        ];

        $this->bcConnector->createReservation($reservation);
        $saleOrder->addLog('Add reservation for line '.$lineNumber.' for '.$orderBigBuy['quantity'].' '.$orderBigBuy['sku']);

        
        $saleOrderLine = new SaleOrderLine();
        $saleOrder->addSaleOrderLine($saleOrderLine);
        $saleOrderLine->setQuantity($orderBigBuy['quantity']);
        $saleOrderLine->setSku($orderBigBuy['sku']);
        $saleOrderLine->setName($itemBc['displayName']);
        $saleOrderLine->setUnitCost($itemBc['unitCost']);
        $saleOrderLine->setLineNumber($lineNumber);
        $saleOrderLine->setPrice($priceBigBuy);
        $saleOrderLine->setBigBuyOrderLine($idOrderBigBuy);
            
        $product = $this->manager->getRepository(Product::class)->findOneBy(['sku'=>$orderBigBuy['sku']]);
        $saleOrderLine->setProduct($product);
            
        $this->manager->persist($saleOrderLine);
        $this->manager->flush();

        return 1;
    }


    protected function getSaleOrder(DateTime $dateTime): SaleOrder
    {
        $saleOrder = $this->manager->getRepository(SaleOrder::class)->findOneBy(['releaseDateString'=>$dateTime->format('Y-m-d H:i')]);
        if(!$saleOrder) {
            $customerBc = $this->bcConnector->getCustomerByNumber(self::CUSTOMER_NUMBER);

        
            $saleOrderBc = new SaleOrderBc();
            $saleOrderBc->customerNumber = self::CUSTOMER_NUMBER;
            $saleOrderBc->shippingAgent = $customerBc['shippingAgentCode'];
            $saleOrderBc->shippingAgentService =  $customerBc['shippingAgentServiceCode'];
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
        $keyOrder  = array_key_exists('order_id', $errors[0]) ? 'order_id' : 'id';
        $lines = [[$keyOrder, 'sku', 'quantity','price', 'error']];

        $this->errors[] = 'Pedido '.$errors[0][$keyOrder].' no puede integrarse en nuestro sistema';
        foreach($errors as $error) {
            $line = [
                $error[$keyOrder],
                $error['sku'],
                $error['quantity'],
                $error['price'],
            ];
            if(array_key_exists('error', $error)) {
                $this->errors[] ='Para SKU solicitado '.$error['sku']. ' con cantidad '.$error['quantity'].' >>> error '.$error['error'];
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
            return 'SKU desconocido '.$saleLine['sku'];
        }

        $product = $this->manager->getRepository(Product::class)->findOneBy(['sku'=>$saleLine['sku']]);
        if(!$product) {
            return 'Sku sin correlación '.$saleLine['sku'];
        }

        /* if(!$product->getPrice()) {
             return 'Sku with no selling price '.$saleLine['sku'];
         }*/

        // check availability
        $stockAvailability = $this->bcConnector->getStockAvailabilityPerProduct($saleLine['sku']);
        if(!$stockAvailability) {
            return 'Sin existencias '.$saleLine['sku'];
        }

        if($stockAvailability['quantityAvailableLAROCA'] < $saleLine['quantity']) {
            return 'No hay suficiente stock '.$saleLine['sku'].' >> '.$stockAvailability['quantityAvailableLAROCA'].' disponible para '.$saleLine['quantity'].' solicitado' ;
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
