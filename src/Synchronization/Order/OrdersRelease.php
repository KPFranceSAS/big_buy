<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Entity\SaleOrderLine;
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
        $errors = [];
        foreach($saleOrders as $saleOrder) {
            try {
                if($saleOrder->getReleaseDate()<$datenow) {
                    $saleOrderBc = $this->bcConnector->getSaleOrderByNumber($saleOrder->getOrderNumber());
                    $this->bcConnector->updateSaleOrder($saleOrderBc['id'], '*', ['pendingToShip'=>false]);
                    $saleOrder->addLog('Marked sale order to be released');
                    $saleOrder->setStatus(SaleOrder::STATUS_WAITING_RELEASE);
                    
                    foreach($saleOrder->getSaleOrderLines() as $saleOrderLine) {
                        $saleOrderLine->setStatus(SaleOrderLine::STATUS_WAITING_RELEASE);
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
            $this->sendEmail->sendAlert('Error OrdersRelease ', implode('<br/>----<br/>', $errors));
        }
    }
       

}
