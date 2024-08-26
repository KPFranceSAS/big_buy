<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\SaleOrder;
use App\Entity\SaleOrderLine;
use App\Mailer\SendEmail;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class OrdersStatusReleaseManually
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
        $saleOrders = $this->manager->getRepository(SaleOrder::class)->findBy(['status'=>SaleOrder::STATUS_OPEN]);
        $errors = [];
        foreach($saleOrders as $saleOrder) {
            try {
                $this->logger->info('Check sale order has been released '.$saleOrder->getOrderNumber());
                if($this->isSaleOrderRelease($saleOrder->getOrderNumber())) {
                    $saleOrder->addLog('Sale order has been released manually. Waiting for shipment', 'info');
                    $saleOrder->setStatus(SaleOrder::STATUS_RELEASED);
                    foreach($saleOrder->getSaleOrderLines() as $saleOrderLine) {
                        $saleOrderLine->setStatus(SaleOrderLine::STATUS_RELEASED);
                    }
                    $this->manager->flush();
                }
            } catch (Exception $e) {
                $error = $e->getMessage().' // '.$e->getFile().' // '.$e->getLine();
                $this->logger->critical($error);
                $errors[]=$error;
            }
            
        }

        if(count($errors)>0) {
            $this->sendEmail->sendAlert('Error OrdersStatusReleaseManually ', implode('<br/>----<br/>', $errors));
        }
    }



   


    protected function isSaleOrderRelease($orderNumber)
    {
        $status =  $this->bcConnector->getStatusOrderByNumber($orderNumber);
        if ($status) {
            $this->logger->info("Status found by reference to the order number " . $orderNumber);
            $statusSaleOrder =  reset($status['statusOrderLines']);
            if (in_array($statusSaleOrder['statusCode'], ["99", "-1"])) {
                $this->logger->info("Sale order is not released " . $orderNumber);
                return false ;
            } else {
                $this->logger->info("Sale order is  released " . $orderNumber);
                return true ;
            }
        }
        $this->logger->info("Status not found for moment " . $orderNumber);
        return false;
    }
    
       

}
