<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Model\SaleOrderBc;
use App\BusinessCentral\Model\SaleOrderLineBc;
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
use Twig\Environment;

class OrdersStatusRelease
{

   
    private $manager;

    private $errors;

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $managerRegistry,
        private KitPersonalizacionSportConnector $bcConnector,
        private SendEmail $sendEmail,
        private Environment $twig,
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }
    
    public function synchronize()
    {
        $saleOrders = $this->manager->getRepository(SaleOrder::class)->findBy(['status'=>SaleOrder::STATUS_OPEN]);
        $datenow = new DateTime();
        foreach($saleOrders as $saleOrder){
            if($saleOrder->getReleaseDate()<$datenow){
                $saleOrderBc = $this->bcConnector->getSaleOrderByNumber($saleOrder->getOrderNumber());
                $this->bcConnector->updateSaleOrder($saleOrderBc['id'], '*', ['pendingShipping'=>false]);
                $saleOrder->addLog('Marked sale order to be released');
                $saleOrder->setStatus(SaleOrder::STATUS_RELEASE);
                $this->manager->flush();
            }
        }
    }
       

}
