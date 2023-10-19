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
        $saleOrderLines = $this->manager->getRepository(SaleOrderLine::class)->findBy(['status'=>SaleOrderLine::STATUS_CONFIRMED]);
      
        $invoices = [];
        if(count($saleOrderLines)>0){

            $csv = Writer::createFromString();
            $csv->setDelimiter(';');
            $csv->insertOne(['invoice_number','delivery_note_number','sku','price','tax','digital_canon','eco_tax','quantity','type','date']);
            foreach($saleOrderLines as $saleOrderLine) {
                    if(!array_key_exists($saleOrderLine->getInvoiceNumber(), $invoices)){
                        $invoices[$saleOrderLine->getInvoiceNumber()] = $this->bcConnector->getSaleInvoiceByNumber($saleOrderLine->getInvoiceNumber());   
                    }
                    if($invoices[$saleOrderLine->getInvoiceNumber()]) {
                        $saleOrderLine->addLog('Confirmed invoice');
                        $productDb = $saleOrderLine->getProduct();
    
                        if($productDb->getCanonDigital()) {
                            $canonDigital = $productDb->getCanonDigital()*$saleOrderLine->getQuantity();
                        } else {
                            $canonDigital = 0;
                        }

                        

                        $csv->insertOne([
                            $invoices[$saleOrderLine->getInvoiceNumber()]["number"],
                            $saleOrderLine->getShipmentNumber(),
                            $saleOrderLine->getSku(),
                            $saleOrderLine->getPrice()*$saleOrderLine->getQuantity(),
                            $productDb->getVatRate(),
                            $canonDigital,
                            0,
                            $saleOrderLine->getQuantity(),
                            'type_product',
                            $invoices[$saleOrderLine->getInvoiceNumber()]['invoiceDate']
                        ]);
                        

                        $saleOrderLine->setStatus(SaleOrderLine::STATUS_INVOICED);
                        $saleOrderLine->getSaleOrder()->updateStatus();    
                    } else {
                        $this->logger->alert('Invoice not found');
                    }
            }
            $this->bigBuyStorage->write('Invoices/'.$saleOrderLine->getSaleOrder()->getOrderNumber().'_'.date('Ymd_His').'.csv', $csv->toString());
            $this->manager->flush();
        }
        
    }
      

}
