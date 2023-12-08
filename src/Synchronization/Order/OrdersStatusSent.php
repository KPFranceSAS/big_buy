<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\SaleOrderLine;
use App\Mailer\SendEmail;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class OrdersStatusSent
{

   
    private $manager;

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $managerRegistry,
        private KitPersonalizacionSportConnector $bcConnector,
        private SendEmail $sendEmail,
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }
    
    public function synchronize()
    {
        $errors = [];
        $saleOrderLines = $this->manager->getRepository(SaleOrderLine::class)->findBy(['status'=>SaleOrderLine::STATUS_RELEASED]);
        foreach($saleOrderLines as $saleOrderLine) {
            try {
                $this->logger->info('Check sale order '.$saleOrderLine->getOrderNumber().' //  '.$saleOrderLine->getLineNumber().' has been sent '.$saleOrderLine->getOrderNumber());
                $shipmentInfo =  $this->isSaleOrderLineSent($saleOrderLine->getOrderNumber(), $saleOrderLine->getLineNumber());
                
                if($shipmentInfo) {
                    $saleOrderLine->addLog('Sale order Line'.$saleOrderLine->getBigBuyOrderLine().' has been sent', 'info');
                    $saleOrderLine->setStatus(SaleOrderLine::STATUS_SENT_BY_WAREHOUSE);
                    $saleOrderLine->setShipmentNumber($shipmentInfo['ShipmentNo']);
                    $saleOrderLine->setInvoiceNumber($shipmentInfo['InvoiceNo']);
                    $saleOrderLine->setShipmentCompany($shipmentInfo['shipmentCompany']);
                    $saleOrderLine->setTrackingNumber($shipmentInfo['trackingNumber']);
                }
                    
            } catch (Exception $e) {
                $error = $e->getMessage().' // '.$e->getFile().' // '.$e->getLine();
                $this->logger->critical($error);
                $errors[]=$error;
            }
            $saleOrderLine->getSaleOrder()->updateStatus();
        }

        if(count($errors)>0) {
            $this->sendEmail->sendAlert('Error OrdersStatusSent ', implode('<br/>----<br/>', $errors));
        }

        $this->manager->flush();
    }



    protected function getMinutesDifference(DateTime $date1, DateTime $date2)
    {
        $interval = $date1->diff($date2, true);
        $minutes = $interval->i + ($interval->h * 60) + ($interval->days * 24 * 60);
    
        return $minutes;
    }


    protected $statuses=[];



    protected function isSaleOrderLineSent($orderNumber, $sequence)
    {

        if(!array_key_exists($orderNumber, $this->statuses)) {
            $status =  $this->bcConnector->getStatusOrderByNumber($orderNumber);
            if (!$status) {
                $this->logger->info("Status not found for moment " . $orderNumber);
                return null;
            } else {
                $this->logger->info("Status found by reference to the order number " . $orderNumber);
                $this->statuses[$orderNumber]=$status;
            }
        }


        foreach($this->statuses[$orderNumber]['statusOrderLines'] as $statutOrderline) {
            if((int)$sequence == $statutOrderline['sequence']) {
                if (in_array($statutOrderline['statusCode'], ["3", "4"]) && (int)$sequence == $statutOrderline['sequence']) {
                    $this->logger->info("Sale order is sent " . $orderNumber);
                    return $statutOrderline;
                }
            }
            
        }

        $this->logger->info("Sequence $sequence not found in " . $orderNumber);
        return null;
    }
    



       

}
