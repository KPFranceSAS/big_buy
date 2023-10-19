<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Mailer\SendEmail;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class OrdersRelease
{

   
    private $manager;


    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $managerRegistry,
        private KitPersonalizacionSportConnector $bcConnector,
        private SendEmail $sendEmail
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }
    
    public function synchronize()
    {
        $saleOrders = $this->manager->getRepository(SaleOrder::class)->findBy(['status'=>SaleOrder::STATUS_OPEN]);
        $datenow = new DateTime();
        foreach($saleOrders as $saleOrder) {
            try {
                if($saleOrder->getReleaseDate()<$datenow) {
                    $saleOrderBc = $this->bcConnector->getSaleOrderByNumber($saleOrder->getOrderNumber());
                    $this->bcConnector->updateSaleOrder($saleOrderBc['id'], '*', ['pendingToShip'=>false]);
                    $saleOrder->addLog('Marked sale order to be released');
                    $saleOrder->setStatus(SaleOrder::STATUS_WAITING_RELEASE);
                    $this->manager->flush();
                                                           
                }
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
                $this->sendEmail->sendAlert('Error OrdersStatusRelease ', $e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
            }
        }
    }
       

}
