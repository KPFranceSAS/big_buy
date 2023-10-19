<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Mailer\SendEmail;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class OrdersStatusConfirmation
{

   
    private $manager;

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $managerRegistry,
        private KitPersonalizacionSportConnector $bcConnector,
        private FilesystemOperator $bigBuyStorage,
        private SendEmail $sendEmail,
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }
    
    public function synchronize()
    {
        $saleOrders = $this->manager->getRepository(SaleOrder::class)->findBy(['status'=>SaleOrder::STATUS_SENT_BY_WAREHOUSE]);
      
        foreach($saleOrders as $saleOrder) {
            try {
                $this->logger->info('Send confirmation order  '.$saleOrder->getOrderNumber());
                $shipmentBc = $this->bcConnector->getSaleShipmentByOrderNumber($saleOrder->getOrderNumber());

                $idLines = [];

                $csv = Writer::createFromString();
                $csv->setDelimiter(';');
                $csv->insertOne(['order_big_buy_id','delivery_note_number', 'sku', 'price','quantity', 'date', 'canon_digital']);
                foreach($shipmentBc['salesShipmentLines'] as $salesShipmentLine) {
                    if(in_array($salesShipmentLine['type'], ['Item', 'Producto'])) {
                        $lines = $saleOrder->getAllLinesSequenceAndSku($salesShipmentLine["lineNo"], $salesShipmentLine['no']);
                        if(count($lines)>0) {
                            $productDb = $this->manager->getRepository(Product::class)->findOneBySku($salesShipmentLine['no']);

                            
                            $qtySend = $salesShipmentLine['quantity'];
                            foreach($lines as $line) {
                                if($productDb->getCanonDigital()) {
                                    $canonDigital = $productDb->getCanonDigital()*$line->getQuantity();
                                } else {
                                    $canonDigital = 0;
                                }
                                $csv->insertOne([$line->getBigBuyOrderLine(), $shipmentBc['number'], $salesShipmentLine['no'], $line->getPrice()*$line->getQuantity(),$line->getQuantity(),date('Y-m-d'), $canonDigital]);
                                $qtySend=$qtySend - $line->getQuantity();
                                $idLines[] = $line->getId();
                            }

                            if($qtySend !=0) {
                                throw new Exception('Quantity sent '.$salesShipmentLine['quantity'].' for SKU '.$salesShipmentLine['no']. ' is not corresponding with sale order');
                            }
                        } else {
                            throw new Exception('No line in Big Buy correspond with sku '.$salesShipmentLine['no']. ' and sequence '.$salesShipmentLine["lineNo"]);
                        }
                    }
                }
            
                $this->bigBuyStorage->write('DeliveryNotes/'.$saleOrder->getOrderNumber().'_'.date('Ymd_His').'.csv', $csv->toString());
                $saleOrder->addLog('Confirmed delivery notes');
                $saleOrder->setStatus(SaleOrder::STATUS_CONFIRMED);


                $errosNotSent = [];
                foreach($saleOrder->getSaleOrderLines() as $saleOrderLine) {
                    if(!in_array($saleOrderLine->getId(), $idLines)) {
                        $errosNotSent[] = $saleOrderLine;
                    }
                }

                if(count($errosNotSent)>0) {
                    $content = '<p>Some lines has not been sent in sale order '.$saleOrder->getOrderNumber().'</p>';
                    foreach($errosNotSent as $erroNotSent) {
                        $content .='<p>BigBuy Order Id'.$erroNotSent->getBigBuyOrderLine().'<br/>'
                                  .'Sku'.$erroNotSent->getSku().'<br/>'
                                  .'Quantiy '.$erroNotSent->getQuantity().'</p>';
                    }
                    $this->sendEmail->sendAlert('Some Lines has not been sent ', $content);
                }




            } catch (Exception $e) {
                $saleOrder->setStatus(SaleOrder::STATUS_ERROR_CONFIRMED);
                $this->logger->critical($e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
                $this->logger->critical($e->getTraceAsString());
                
                $this->sendEmail->sendAlert('Error Order status confirmation ', $e->getMessage().' <br/> '.$e->getFile().' <br/>'.$e->getLine().'<br/>'.$e->getTraceAsString());
            }
            $this->manager->flush();
        }
    }
      

}
