<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Mailer\SendEmail;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class OrdersStatusInvoice
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
        $saleOrders = $this->manager->getRepository(SaleOrder::class)->findBy(['status'=>SaleOrder::STATUS_CONFIRMED]);
      
        foreach($saleOrders as $saleOrder) {
            try {
                $this->logger->info('Send confirmation order  '.$saleOrder->getOrderNumber());
                $invoiceBc = $this->bcConnector->getSaleInvoiceByOrderNumber($saleOrder->getOrderNumber());
                if($invoiceBc) {
                    $saleOrder->addLog('Confirmed invoice');
                    $saleOrder->setInvoiceNumber($invoiceBc["number"]);
                    $csv = Writer::createFromString();
                    $csv->setDelimiter(';');
                    $csv->insertOne(['invoice_number','delivery_note_number','sku','price','tax','digital_canon','eco_tax','quantity','type','date']);
                    foreach($invoiceBc['salesInvoiceLines'] as $salesInvoiceLine) {
                        if(in_array($salesInvoiceLine['lineType'], ['Item', 'Producto'])) {
                            $sku = $salesInvoiceLine['lineDetails']['number'];
                            $productDb = $this->manager->getRepository(Product::class)->findOneBySku($sku);

                            if($productDb->getCanonDigital()){
                                $canonDigital = $productDb->getCanonDigital()*$salesInvoiceLine['quantity'];
                                $netAmount = $salesInvoiceLine['netTaxAmount'] + round($canonDigital*0.21,0,2);
                            } else {
                                $canonDigital = 0;
                                $netAmount = $salesInvoiceLine['netTaxAmount'];
                            }

                            $csv->insertOne([
                                $invoiceBc["number"],
                                $saleOrder->getShipmentNumber(),
                                $sku,
                                $salesInvoiceLine['netAmount'],
                                $netAmount,
                                $canonDigital,
                               0,
                               $salesInvoiceLine['quantity'],
                               'type_product',
                               $invoiceBc['invoiceDate']
                            ]);
                        }
                    }
            
                    $this->bigBuyStorage->write('Invoices/'.$saleOrder->getOrderNumber().'_'.date('Ymd_His').'.csv', $csv->toString());
                    $saleOrder->setStatus(SaleOrder::STATUS_INVOICED);
                } else {
                    $this->logger->alert('Invoice not found');
                }
                
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
                $this->sendEmail->sendAlert('Error OrdersStatusInvoice ', $e->getMessage().' // '.$e->getFile().' // '.$e->getLine());
            }
            $this->manager->flush();
        }
    }
      

}
