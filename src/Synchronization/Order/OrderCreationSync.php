<?php

namespace App\Synchronization\Order;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Model\CustomerPayment;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Entity\Product;
use App\Entity\WebOrder;
use App\Magento\MagentoClient;
use App\Mailer\SendEmail;
use App\Mollie\MollieAggregator;
use App\Transformer\OrderTransformer;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use TypeError;

class OrderCreationSync
{

    public const CUSTOMER_NUMBER="";


    private $manager;

    private $errors;

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $managerRegistry,
        private BusinessCentralAggregator $businessCentralAggregator,
        private SendEmail $sendEmail,
        private Environment $twig,
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }
    
    public function synchronize()
    {
        
    }


}
