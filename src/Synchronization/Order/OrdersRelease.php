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
                    $minimumPrices = [];
                    foreach($saleOrder->getSaleOrderLines() as $saleOrderLine) {
                        if(!array_key_exists($saleOrderLine->getSku(), $minimumPrices)) {
                            $product = $this->manager->getRepository(Product::class)->findOneBySku($saleOrderLine->getSku());
                            if($product->getMinimumPrice() && $product->getMinimumPrice() > $saleOrderLine->getPrice()) {
                                $minimumPrices[$saleOrderLine->getSku()] = [
                                    'sku'=> $saleOrderLine->getSku(),
                                    'price'=>$saleOrderLine->getPrice(),
                                    'minimumPrice'=>$product->getMinimumPrice(),
                                     "name"=>$product->getNameErp()
                                    ];
                            }
                        }
                        
                    }

                    if(count($minimumPrices)>0) {
                        $content= '<p>The sale order '.$saleOrder->getOrderNumber().' is ready to be released but some prices are below minimum. please request approval to release it</p>';
                        $content.= '<p>Here the skus and prices that cause problems</p>';
                        foreach($minimumPrices as $minimumPrice) {
                            $content.="Product ".$minimumPrice['sku'].' - '.$minimumPrice['name'].' sell '.$minimumPrice['price'].' above minimum price '.$minimumPrice['minimumPrice'].'<br/>';
                        }

                        $this->sendEmail->sendEmail(
                            ['administracion@kpsport.com', 'devops@kpsport.com'],
                             'Need to approval request '.$saleOrder->getOrderNumber(), 
                             $content
                        );
                        $saleOrder->addLog('Sent emails to administracion@kpsport.com to request approval');
                        $this->manager->flush();
                    }
                                       
                }
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
                $this->sendEmail->sendAlert('Error OrdersStatusRelease ', $e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
            }
        }
    }
       

}
