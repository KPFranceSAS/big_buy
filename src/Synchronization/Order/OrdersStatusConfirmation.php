<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
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

                $csv = Writer::createFromString();
                $csv->setDelimiter(';');
                $csv->insertOne(['order_big_buy_id','delivery_note_number', 'sku', 'price','quantity', 'date']);
                foreach($shipmentBc['salesShipmentLines'] as $salesShipmentLine) {
                    if(in_array($salesShipmentLine['type'], ['Item', 'Producto'])) {
                        $line = $saleOrder->getLineSequence($salesShipmentLine["lineNo"], $salesShipmentLine['no']);
                        if($line) {
                            $csv->insertOne([$line->getBigBuyOrderLine(), $shipmentBc['number'], $salesShipmentLine['no'], $line->getPrice(),$salesShipmentLine['quantity'],date('Y-m-d')]);
                        } else {
                            $csv->insertOne(['', $shipmentBc['number'], $salesShipmentLine['no'],null,$salesShipmentLine['quantity'],date('Y-m-d')]);
                        }
                    }
                }
            
                $this->bigBuyStorage->write('DeliveryNotes/'.$saleOrder->getOrderNumber().'_'.date('Ymd_His').'.csv', $csv->toString());
                $saleOrder->addLog('Confirmed delivery notes');
                $saleOrder->setStatus(SaleOrder::STATUS_CONFIRMED);
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
                $this->sendEmail->sendAlert('Error Order status confirmation ', $e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
            }
            $this->manager->flush();
        }
    }
      

}
