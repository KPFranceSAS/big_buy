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
                    if($nbMinutesSinceRelease > 30) {
                        $log = 'Sale order '.$saleOrder->getOrderNumber().' is not released for Big Buy. Please check what happens (stock, credit, or minimum prices ....)';
                        if($saleOrder->haveNoLogWithMessage($log)) {
                            $saleOrder->addLog($log, 'error');
                            $this->logger->critical($log);
                            $this->sendEmail->sendEmail(['devops@kpsport.com', 'administracion@kpsport.com'], 'Pending release order '.$saleOrder->getOrderNumber(), $log);
                        }
                    }
                } else {
                    $saleOrder->addLog('Sale order has been released. Waiting for shipment', 'info');
                    $saleOrder->setStatus(SaleOrder::STATUS_RELEASED);
                }
                $this->manager->flush();


                if($saleOrder->getStatus()==SaleOrder::STATUS_RELEASED) {
                    $ordersGather = [];
                    foreach($saleOrder->getSaleOrderLines() as $saleOrderLine) {
                        if(!array_key_exists($saleOrderLine->getSku(), $ordersGather)) {
                            $product = $this->manager->getRepository(Product::class)->findOneBySku($saleOrderLine->getSku());
                            $ordersGather[$saleOrderLine->getSku()] = [
                                'sku'=> $saleOrderLine->getSku(),
                                'qty'=>0,
                                 "name"=>$product->getNameErp()
                            ];
                        }
                        $ordersGather[$saleOrderLine->getSku()]['qty'] = $ordersGather[$saleOrderLine->getSku()]['qty']+$saleOrderLine->getQuantity();
                    }

                    $content= '<p>The sale order '.$saleOrder->getOrderNumber().' is ready to be picekd up</p>';
                    $content.= '<p>Here the skus and quantities gathered</p>';
                    $content.='<table width="100%" cellpadding="0" cellspacing="0" style="min-width:100%;  border-collapse: collapse; border-style: solid; border-color: # aeabab;" border="1px">';
                    $content.='<tr>';
                    $content.='<td style="padding:5px; border: 1px solid #aeabab;" align="left"><strong>Sku</strong></td>';
                    $content.='<td style="padding:5px; border: 1px solid #aeabab;" align="left"><strong>Name</strong></td>';
                    $content.='<td style="padding:5px; border: 1px solid #aeabab;" align="left"><strong>Qty</strong></td>';
                    $content.='</tr>';
                    foreach($ordersGather as $orderGather) {
                        $content.='<tr>';
                        $content.='<td style="padding:5px; border: 1px solid #aeabab;" align="left">'. $orderGather['sku'].'</td>';
                        $content.='<td style="padding:5px; border: 1px solid #aeabab;" align="left">'. $orderGather['name'].'</td>';
                        $content.='<td style="padding:5px; border: 1px solid #aeabab;" align="left">'. $orderGather['qty'].'</td>';
                        $content.='</tr>';
                    }
                    $content.='</table>';

                    $this->sendEmail->sendEmail(
                        ['logistica@logisticacel.com', 'devops@kpsport.com'],
                        'Order content '.$saleOrder->getOrderNumber(),
                        $content
                    );
                    $saleOrder->addLog('Sent emails to logistica@logisticacel.com to describe content');
                    $this->manager->flush();
                }
                



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
