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
        $saleOrderLines = $this->manager->getRepository(SaleOrderLine::class)->findBy(['status'=>SaleOrderLine::STATUS_SENT_BY_WAREHOUSE]);
      
        if(count($saleOrderLines)>0) {
            $csv = Writer::createFromString();
            $csv->setDelimiter(';');
            $csv->insertOne(['order_big_buy_id','delivery_note_number', 'sku', 'price','quantity', 'date', 'canon_digital']);
            

            foreach($saleOrderLines as $saleOrderLine) {
                $productDb = $saleOrderLine->getProduct();
                if($productDb && $productDb->getCanonDigital()) {
                    $canonDigital = $productDb->getCanonDigital()*$saleOrderLine->getQuantity();
                } else {
                    $canonDigital = 0;
                }
                $csv->insertOne(
                    [
                        $saleOrderLine->getBigBuyOrderLine(),
                        $saleOrderLine->getShipmentNumber(),
                        $saleOrderLine->getSku(),
                        $saleOrderLine->getPrice()*$saleOrderLine->getQuantity(),
                        $saleOrderLine->getQuantity(),
                        date('Y-m-d'),
                        $canonDigital]
                );

                $saleOrderLine->addLog('Confirmed delivery notes');
                $saleOrderLine->setStatus(SaleOrderLine::STATUS_CONFIRMED);
                $saleOrderLine->getSaleOrder()->updateStatus();
            }
            $this->bigBuyStorage->write('DeliveryNotes/'.$saleOrderLine->getSaleOrder()->getOrderNumber().'_'.date('Ymd_His').'.csv', $csv->toString());
        }
        $this->manager->flush();
    }
      

}
