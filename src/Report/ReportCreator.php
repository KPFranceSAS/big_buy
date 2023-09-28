<?php

namespace App\Report;

use App\Entity\Product;
use App\Entity\SaleOrder;
use App\Entity\SaleOrderLine;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;

class ReportCreator
{


    private $manager;


    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $managerRegistry
    ) {
        $this->manager = $this->managerRegistry->getManager();
    }



    public function createReport(DateTime $dateTime): ReportDto
    {

        $reportDto = new ReportDto($dateTime);
        $this->addDatas($reportDto);
       
        return $reportDto;
    }



    public function addDatas(ReportDto $reportDto)
    {
        $reportDto->saleOrderLines = $this->manager->getRepository(SaleOrderLine::class)->findAllSaleLinesBetween($reportDto->pastDateStartPeriod, $reportDto->dateEndPeriod);
    }

}
