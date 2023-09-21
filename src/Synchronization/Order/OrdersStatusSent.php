<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\SaleOrder;
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
        $saleOrders = $this->manager->getRepository(SaleOrder::class)->findBy(['status'=>SaleOrder::STATUS_RELEASED]);
        $datenow = new DateTime();
       
        foreach($saleOrders as $saleOrder) {
            try {
                $this->logger->info('Check sale order has been sent '.$saleOrder->getOrderNumber());
                if(!$this->isSaleOrderSent($saleOrder->getOrderNumber())) {
                    $nbMinutesSinceRelease = $this->getMinutesDifference($saleOrder->getReleaseDate(), $datenow);
                    $this->logger->info('Sale order has been marked to be sent '.$nbMinutesSinceRelease.' minutes ago');
                    if($nbMinutesSinceRelease > 720) {
                        $log = 'Sale order '.$saleOrder->getOrderNumber().' is not sent by warehouse. It should be received in Valencia '.$saleOrder->getArrivalTime()->format('d/m/Y H:i');
                        if($saleOrder->haveNoLogWithMessage($log)) {
                            $saleOrder->addLog($log, 'error');
                            $this->logger->critical($log);
                            $this->sendEmail->sendEmail(['devops@kpsport.com', 'administracion@kpsport.com'], 'Pending shipment order '.$saleOrder->getOrderNumber(), $log);
                        }
                    }
                } else {
                    $saleOrder->addLog('Sale order has been sent', 'info');
                    $saleOrder->setStatus(SaleOrder::STATUS_SENT_BY_WAREHOUSE);
                    $shipmentBc = $this->bcConnector->getSaleShipmentByOrderNumber($saleOrder->getOrderNumber());
                    $saleOrder->setShipmentNumber($shipmentBc['number']);
                }
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
                $this->sendEmail->sendAlert('Error OrdersStatusSent ', $e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
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
