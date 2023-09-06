<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\SaleOrder;
use App\Mailer\SendEmail;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class OrdersStatusRelease
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
        $saleOrders = $this->manager->getRepository(SaleOrder::class)->findBy(['status'=>SaleOrder::STATUS_WAITING_RELEASE]);
        $datenow = new DateTime();
       
        foreach($saleOrders as $saleOrder) {
            try {
                $this->logger->info('Check sale order has been release '.$saleOrder->getOrderNumber());
                if(!$this->isSaleOrderRelease($saleOrder->getOrderNumber())) {
                    $nbMinutesSinceRelease = $this->getMinutesDifference($saleOrder->getReleaseDate(), $datenow);
                    $this->logger->info('Sale order has been marked to be released '.$nbMinutesSinceRelease.' minutes ago');
                    if($nbMinutesSinceRelease > 60) {
                        $log = 'Sale order '.$saleOrder->getOrderNumber().'  is not released. Please check what happens (stock, credit, customer misconfiguration, ....)';
                        if($saleOrder->haveNoLogWithMessage($log)) {
                            $saleOrder->addLog($log, 'error');
                            $this->logger->critical($log);
                            $this->sendEmail->sendAlert('Pending release order '.$saleOrder->getOrderNumber(), $log);
                        }
                    }
                } else {
                    $saleOrder->addLog('Sale order has been released. Waiting for shipment', 'info');
                    $saleOrder->setStatus(SaleOrder::STATUS_RELEASED);
                }
                $this->manager->flush();
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
                $this->sendEmail->sendAlert('Error OrdersStatusRelease ', $e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
            }
            
        }
    }



    protected function getMinutesDifference(DateTime $date1, DateTime $date2)
    {
        $interval = $date1->diff($date2, true);
        $minutes = $interval->i + ($interval->h * 60) + ($interval->days * 24 * 60);
    
        return $minutes;
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
