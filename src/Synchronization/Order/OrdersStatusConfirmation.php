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
                $csv->insertOne(['delivery note number', 'sku', 'price','quantity']);
                $csv->setDelimiter(';');
                foreach($shipmentBc['salesShipmentLines'] as $salesShipmentLine){
                    if(in_array($salesShipmentLine['type'], ['Item', 'Producto'])){
                        $line = $saleOrder->getLineSequence($salesShipmentLine["lineNo"], $salesShipmentLine['no']);
                        if($line){
                            $csv->insertOne([$shipmentBc['number'], $salesShipmentLine['no'], $line->getPrice(),$salesShipmentLine['quantity']]);
                        } else {

                            $csv->insertOne([$shipmentBc['number'], $salesShipmentLine['no'],null,$salesShipmentLine['quantity']]);
                        }
                    } 
                }
            
                $this->bigBuyStorage->write('DeliveryNotes/'.$saleOrder->getOrderNumber().'_'.date('Ymd_His').'.csv', $csv->toString());
                $saleOrder->addLog('Confirmed delivery notes');
                $saleOrder->setStatus(SaleOrder::STATUS_CONFIRMED);
            } catch (Exception $e){
                $this->logger->critical( $e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
                $this->sendEmail->sendAlert('Error Order status confirmation ', $e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
            }
            $this->manager->flush();
        }
    }



    protected function getMinutesDifference(DateTime $date1, DateTime $date2)
    {
        $interval = $date1->diff($date2, true);
        $minutes = $interval->i + ($interval->h * 60) + ($interval->days * 24 * 60);
    
        return $minutes;
    }



    protected function isSaleOrderSent($orderNumber)
    {
        $status =  $this->bcConnector->getStatusOrderByNumber($orderNumber);
        if ($status) {
            $this->logger->info("Status found by reference to the order number " . $orderNumber);
            $statusSaleOrder =  reset($status['statusOrderLines']);
            if (in_array($statusSaleOrder['statusCode'], ["3", "4"])) {
                $this->logger->info("Sale order is sent " . $orderNumber);
                return true ;
            } else {
                $this->logger->info("Sale order is not  released " . $orderNumber);
                return false ;
            }
        }
        $this->logger->info("Status not found for moment " . $orderNumber);
        return false;
    }
    
       

}
