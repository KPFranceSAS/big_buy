<?php

namespace App\Report;

use DateInterval;
use DateTime;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ReportDto
{
    /** @var \DateTime */
    public $dateReport;

    /** @var \DateTime */
    public $pastDateReport;

    /** @var \DateTime */
    public $dateStartPeriod;

    /** @var \DateTime */
    public $dateEndPeriod;

    /** @var \DateTime */
    public $pastDateStartPeriod;
    
    /** @var \DateTime */
    public $pastDateEndPeriod;

    /** @var \App\Entity\SaleOrderLine[] */
    public $saleOrderLines;

    /** @var \App\Report\ReportDto */
    public $pastReport;

    private $dailyRevenue;

    private $weeklyRevenue;

    private $pastDailyRevenue;

    private $pastWeeklyRevenue;


    private $dailyCost;

    private $weeklyCost;

    private $pastDailyCost;

    private $pastWeeklyCost;

    private $dailyNumberOfOrders;

    private $weeklyNumberOfOrders;

    private $pastDailyNumberOfOrders;

    private $pastWeeklyNumberOfOrders;

    private $dailyMargin;

    private $weeklyMargin;

    private $pastDailyMargin;

    private $pastWeeklyMargin;
    private $chartBuilder;




    public function __construct(DateTime $dateReport)
    {
        $this->dateReport= $dateReport;
        $this->dateStartPeriod = clone($this->dateReport);
        $this->dateStartPeriod->modify('Monday this week');
        $this->dateEndPeriod = clone($this->dateReport);
        $this->dateEndPeriod->modify('Sunday this week');

        $this->pastDateReport= clone($dateReport);
        $this->pastDateReport->sub(new DateInterval('P1D'));
        $this->pastDateStartPeriod = clone($this->dateStartPeriod);
        $this->pastDateStartPeriod->sub(new DateInterval('P7D'));
        $this->pastDateEndPeriod = clone($this->dateEndPeriod);
        $this->pastDateEndPeriod->sub(new DateInterval('P7D'));
    }


    public function calculatePercentage($initialValue, $newValue)
    {
        if($initialValue && $newValue && $initialValue > 0 && $newValue >0) {
            $percentage = (($newValue - $initialValue) / $initialValue)*100;
            return  round($percentage, 2);
        }
        return null;
    }

    public function getPeriodRevenue(DateTime $dateDebutRevenue, DateTime $dateFinRevenue)
    {
        $periodRevenue=0;
        foreach($this->saleOrderLines as $saleOrderLine) {
            if($saleOrderLine->getCreatedAt()->format('Ymd')>= $dateDebutRevenue->format('Ymd') &&
            $saleOrderLine->getCreatedAt()->format('Ymd') <= $dateFinRevenue->format('Ymd')
            ) {
                $periodRevenue += $saleOrderLine->getTotalPrice();
            }
        }
        return $periodRevenue;
    }


    public function getDayRevenue(DateTime $dayRevenue)
    {
        return $this->getPeriodRevenue($dayRevenue, $dayRevenue);
    }



    public function getPeriodCost(DateTime $dateDebutRevenue, DateTime $dateFinRevenue)
    {
        $periodCost=0;
        foreach($this->saleOrderLines as $saleOrderLine) {
            if($saleOrderLine->getCreatedAt()->format('Ymd')>= $dateDebutRevenue->format('Ymd') &&
            $saleOrderLine->getCreatedAt()->format('Ymd') <= $dateFinRevenue->format('Ymd')
            ) {
                $periodCost += $saleOrderLine->getTotalCost();
            }
        }
        return $periodCost;
    }


    public function getNbOrdersDay(DateTime $dateOrder)
    {
        return $this->getNbOrdersPeriod($dateOrder, $dateOrder);
    }


    public function getNbOrdersPeriod(DateTime $dateDebutRevenue, DateTime $dateFinRevenue)
    {
        $orderIds = [];
        foreach($this->saleOrderLines as $saleOrderLine) {
            if($saleOrderLine->getCreatedAt()->format('Ymd')>= $dateDebutRevenue->format('Ymd') &&
            $saleOrderLine->getCreatedAt()->format('Ymd') <= $dateFinRevenue->format('Ymd')
            ) {
                $orderIds[] = $saleOrderLine->getBigBuyOrderLine();
            }
        }
        array_unique($orderIds);
        return count($orderIds);
    }


    public function getDailyRevenue()
    {
        if(!$this->dailyRevenue) {
            $this->dailyRevenue= $this->getDayRevenue($this->dateReport);
        }
        return $this->dailyRevenue;
    }


    public function getPastDailyRevenue()
    {
        if(!$this->pastDailyRevenue) {
            $this->pastDailyRevenue= $this->getDayRevenue($this->pastDateReport);
        }
        return $this->pastDailyRevenue;
    }


    
    public function getDailyProgress()
    {
        return $this->calculatePercentage($this->getPastDailyRevenue(), $this->getDailyRevenue());
    }



    public function getWeeklyRevenue()
    {
        if(!$this->weeklyRevenue) {
            $this->weeklyRevenue= $this->getPeriodRevenue($this->dateStartPeriod, $this->dateEndPeriod);
        }
        return $this->weeklyRevenue;
    }


    public function getPastWeeklyRevenue()
    {
        if(!$this->pastWeeklyRevenue) {
            $this->pastWeeklyRevenue= $this->getPeriodRevenue($this->pastDateStartPeriod, $this->pastDateEndPeriod);
        }
        return $this->pastWeeklyRevenue;
    }

    public function getWeeklyProgress()
    {
        return $this->calculatePercentage($this->getPastWeeklyRevenue(), $this->getWeeklyRevenue());
    }




    public function getDailyCost()
    {
        if(!$this->dailyCost) {
            $this->dailyCost= round($this->getPeriodCost($this->dateReport, $this->dateReport), 2);
        }
        return $this->dailyCost;
    }


    public function getPastDailyCost()
    {
        if(!$this->pastDailyCost) {
            $this->pastDailyCost= round($this->getPeriodCost($this->pastDateReport, $this->pastDateReport), 2);
        }
        return $this->pastDailyCost;
    }



    public function getWeeklyCost()
    {
        if(!$this->weeklyCost) {
            $this->weeklyCost= round($this->getPeriodCost($this->dateStartPeriod, $this->dateEndPeriod), 2);
        }
        return $this->weeklyCost;
    }


    public function getPastWeeklyCost()
    {
        if(!$this->pastWeeklyCost) {
            $this->pastWeeklyCost= round($this->getPeriodCost($this->pastDateStartPeriod, $this->pastDateEndPeriod), 2);
        }
        return $this->pastWeeklyCost;
    }

    



    public function getDailyNumberOfOrders()
    {
        if(!$this->dailyNumberOfOrders) {
            $this->dailyNumberOfOrders= $this->getNbOrdersDay($this->dateReport);
        }
        return $this->dailyNumberOfOrders;
    }


    public function getPastDailyNumberOfOrders()
    {
        if(!$this->pastDailyNumberOfOrders) {
            $this->pastDailyNumberOfOrders= $this->getNbOrdersDay($this->pastDateReport);
        }
        return $this->pastDailyNumberOfOrders;
    }


    
    public function getDailyNumberOrdersProgress()
    {
        return $this->calculatePercentage($this->getPastDailyNumberOfOrders(), $this->getDailyNumberOfOrders());
    }



    public function getWeeklyNumberOfOrders()
    {
        if(!$this->weeklyNumberOfOrders) {
            $this->weeklyNumberOfOrders= $this->getNbOrdersPeriod($this->dateStartPeriod, $this->dateEndPeriod);
        }
        return $this->weeklyNumberOfOrders;
    }


    public function getPastWeeklyNumberOfOrders()
    {
        if(!$this->pastWeeklyNumberOfOrders) {
            $this->pastWeeklyNumberOfOrders= $this->getNbOrdersPeriod($this->pastDateStartPeriod, $this->pastDateEndPeriod);
        }
        return $this->pastWeeklyNumberOfOrders;
    }


    
    public function getWeeklyNumberOrdersProgress()
    {
        return $this->calculatePercentage($this->getPastWeeklyNumberOfOrders(), $this->getWeeklyNumberOfOrders());
    }



    public function getDailyMargin()
    {
        if(!$this->dailyMargin) {
            $this->dailyMargin= $this->getDailyRevenue()- $this->getDailyCost();
        }
        return $this->dailyMargin;
    }


    public function getPastDailyMargin()
    {
        if(!$this->pastDailyMargin) {
            $this->pastDailyMargin= $this->getPastDailyRevenue()- $this->getPastDailyCost();
        }
        return $this->pastDailyMargin;
    }


    
    public function getDailyMarginProgress()
    {
        return $this->calculatePercentage($this->getPastDailyMargin(), $this->getDailyMargin());
    }



    public function getWeeklyMargin()
    {
        if(!$this->weeklyMargin) {
            $this->weeklyMargin= $this->getWeeklyRevenue()- $this->getWeeklyCost();
        }
        return $this->weeklyMargin;
    }


    public function getPastWeeklyMargin()
    {
        if(!$this->pastWeeklyMargin) {
            $this->pastWeeklyMargin= $this->getPastWeeklyRevenue()- $this->getPastWeeklyCost();
        }
        return $this->pastWeeklyMargin;
    }


    
    public function getWeeklyMarginProgress()
    {
        return $this->calculatePercentage($this->getPastWeeklyMargin(), $this->getWeeklyMargin());
    }




    public function getDailyMarginRate()
    {
        return $this->calculateMarginRate($this->getDailyRevenue(), $this->getDailyCost());
    }


    public function getPastDailyMarginRate()
    {
        return $this->calculateMarginRate($this->getPastDailyRevenue(), $this->getPastDailyCost());
    }


    
    public function getDailyMarginRateProgress()
    {
        return $this->calculateMarginRate($this->getPastDailyMarginRate(), $this->getDailyMarginRate());
    }


    

    public function getWeeklyMarginRate()
    {
        return $this->calculateMarginRate($this->getWeeklyRevenue(), $this->getWeeklyCost());
    }


    public function getPastWeeklyMarginRate()
    {
        return $this->calculateMarginRate($this->getPastWeeklyRevenue(), $this->getPastWeeklyCost());
    }


    
    public function getWeeklyMarginRateProgress()
    {
        return $this->calculateMarginRate($this->getPastWeeklyMarginRate(), $this->getWeeklyMarginRate());
    }




    public function calculateMarginRate($price, $cost)
    {
        if($price && $cost && $price > 0 && $cost >0) {
            $percentage = (($price - $cost) / $price)*100;
            return  round($percentage, 2);
        }
        return null;
    }



    public function getChartRevenue()
    {
        $chart = new Chart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'datasets' => [
                
                [
                    'label' => 'Revenues week '.$this->pastDateStartPeriod->format('W'),
                    'type' => 'bar',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'data' => $this->getRevenuePerDayPerPeriod($this->pastDateStartPeriod, $this->pastDateEndPeriod),
                    "yAxisID" => 'y',
                ],
                [
                    'label' => 'Revenues week '.$this->dateStartPeriod->format('W'),
                    'type' => 'bar',
                    'backgroundColor' => 'rgb(6,121,183)',
                    'data' => $this->getRevenuePerDayPerPeriod($this->dateStartPeriod, $this->dateEndPeriod),
                    "yAxisID" => 'y',
                ],
               
            ],
        ]);


        $chart->setOptions([
            'scales' => [
                'y' => [
                    'type'=> 'linear',
                    'display'=> true,
                    'position'=> 'left',
                    'title' => [
                        'display'=> true,
                        'text'=> 'Revenue €',
                    ]
                ],
            ],
        ]);

        return $chart;
    }




    public function getChartNbOrder()
    {
        $chart = new Chart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'datasets' => [
                
                [
                    'label' => 'Week '.$this->pastDateStartPeriod->format('W'),
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'type' => 'bar',
                    'data' => $this->getOrderPerDayPerPeriod($this->pastDateStartPeriod, $this->pastDateEndPeriod),
                ],
                [
                    'label' => 'Week '.$this->dateStartPeriod->format('W'),
                    'backgroundColor' => 'rgb(6,121,183)',
                    'type' => 'bar',
                    'data' => $this->getOrderPerDayPerPeriod($this->dateStartPeriod, $this->dateEndPeriod),
                ]
               
            ],
        ]);


        $chart->setOptions([
            'scales' => [
                'y' => [
                    'type'=> 'linear',
                    'display'=> true,
                    'position'=> 'left',
                    'title' => [
                        'display'=> true,
                        'text'=> 'Orders number',
                    ]
                ],
            ],
        ]);

        return $chart;
    }


    public function getChartRevenueByBrand()
    {

        $revenueByBrand = $this->getRevenueByBrand($this->dateStartPeriod, $this->dateEndPeriod);
        $chart = new Chart(Chart::TYPE_PIE);
        $chart->setData([
            'labels' => array_keys($revenueByBrand),
            'datasets' => [
                [
                    'label' => 'Week '.$this->dateStartPeriod->format('W'),
                    'data' => array_values($revenueByBrand),
                    'backgroundColor' => $this->getBackgroundColors(count($revenueByBrand)),
                ],
            ],
        ]);
        return $chart;
    }



    public function getChartQuantityByBrand()
    {
        $revenueByBrand = $this->getQuantityByBrand($this->dateStartPeriod, $this->dateEndPeriod);
        $chart = new Chart(Chart::TYPE_PIE);
        $chart->setData([
            'labels' => array_keys($revenueByBrand),
            'datasets' => [
                [
                    'label' => 'Week '.$this->dateStartPeriod->format('W'),
                    'data' => array_values($revenueByBrand),
                    'backgroundColor' => $this->getBackgroundColors(count($revenueByBrand)),
                ],
            ],
        ]);
        return $chart;
    }
    
    public function getChartMarginByBrand()
    {
        $revenueByBrand = $this->getMarginByBrand($this->dateStartPeriod, $this->dateEndPeriod);
        $chart = new Chart(Chart::TYPE_PIE);
        $chart->setData([
            'labels' => array_keys($revenueByBrand),
            'datasets' => [
                [
                    'label' => 'Week '.$this->dateStartPeriod->format('W'),
                    'data' => array_values($revenueByBrand),
                    'backgroundColor' => $this->getBackgroundColors(count($revenueByBrand)),
                ],
            ],
        ]);
        return $chart;
    }


    
    public function getChartMarginRateByBrand()
    {
        $revenueByBrand = $this->getMarginRateByBrand($this->dateStartPeriod, $this->dateEndPeriod);
        $chart = new Chart(Chart::TYPE_PIE);
        $chart->setData([
            'labels' => array_keys($revenueByBrand),
            'datasets' => [
                [
                    'label' => 'Week '.$this->dateStartPeriod->format('W'),
                    'data' => array_values($revenueByBrand),
                    'backgroundColor' => $this->getBackgroundColors(count($revenueByBrand)),
                ],
            ],
        ]);
        return $chart;
    }


    public function getAllSaleLineForPeriod(DateTime $dateStart, DateTime $dateEnd)
    {
        $saleOrdersLine = [];
        foreach($this->saleOrderLines as $saleOrderLine) {
            if($saleOrderLine->getCreatedAt()->format('Ymd')>= $dateStart->format('Ymd') &&
            $saleOrderLine->getCreatedAt()->format('Ymd') <= $dateEnd->format('Ymd')
            ) {
                $saleOrdersLine[]= $saleOrderLine;
            }
        }
        return $saleOrdersLine;
    }


    public function getAllSaleLineForPeriodByBrand(DateTime $dateStart, DateTime $dateEnd)
    {
        $saleOrderLines = $this->getAllSaleLineForPeriod($dateStart, $dateEnd);
        $brands =[];
        foreach($saleOrderLines as $saleOrderLine) {
            $brand = $saleOrderLine->getBrand();
            if(!array_key_exists($brand, $brands)) {
                $brands[$brand]=[];
            }
            $brands[$brand][]=$saleOrderLine;
        }
        ksort($brands);
        return $brands;
    }


    public function getRevenueByBrand(DateTime $dateStart, DateTime $dateEnd)
    {
        $saleOrderLinesByBrand = $this->getAllSaleLineForPeriodByBrand($dateStart, $dateEnd);
        $brandRevenues = [];
        foreach($saleOrderLinesByBrand as $brand => $saleOrderLines) {
            $revenue = 0;
            foreach($saleOrderLines as $saleOrderLine) {
                $revenue += $saleOrderLine->getTotalPrice();
            }
            $brandRevenues[$brand]=$revenue;
        }
        return $brandRevenues;
    }


    public function getQuantityByBrand(DateTime $dateStart, DateTime $dateEnd)
    {
        $saleOrderLinesByBrand = $this->getAllSaleLineForPeriodByBrand($dateStart, $dateEnd);
        $brands = [];
        foreach($saleOrderLinesByBrand as $brand => $saleOrderLines) {
            $qty = 0;
            foreach($saleOrderLines as $saleOrderLine) {
                $qty += $saleOrderLine->getQuantity();
            }
            $brands[$brand]=$qty;
        }
        return $brands;
    }



    public function getMarginByBrand(DateTime $dateStart, DateTime $dateEnd)
    {
        $saleOrderLinesByBrand = $this->getAllSaleLineForPeriodByBrand($dateStart, $dateEnd);
        $brands = [];
        foreach($saleOrderLinesByBrand as $brand => $saleOrderLines) {
            $qty = 0;
            foreach($saleOrderLines as $saleOrderLine) {
                $qty += $saleOrderLine->getMargin();
            }
            $brands[$brand]=$qty;
        }
        return $brands;
    }

    public function getMarginRateByBrand(DateTime $dateStart, DateTime $dateEnd)
    {
        $saleOrderLinesByBrand = $this->getAllSaleLineForPeriodByBrand($dateStart, $dateEnd);
        $brands = [];
        foreach($saleOrderLinesByBrand as $brand => $saleOrderLines) {
            $revenue = 0;
            $cost = 0;
            foreach($saleOrderLines as $saleOrderLine) {
                $revenue += $saleOrderLine->getTotalPrice();
                $cost +=  $saleOrderLine->getTotalCost();
            }
            $brands[$brand]=$this->calculateMarginRate($revenue, $cost);
        }
        return $brands;
    }


    public function getDayPeriod(DateTime $dateStart, DateTime $dateEnd)
    {
        $dateBegin = clone($dateStart);
        $days = [];
        while($dateBegin->format('Ymd')<=$dateEnd->format('Ymd')) {
            $days[] = $dateBegin->format('d-m-Y');
            $dateBegin->add(new DateInterval('P1D'));
        }
        return $days;
    }


    public function getRevenuePerDayPerPeriod(DateTime $dateStart, DateTime $dateEnd)
    {
        $dateBegin = clone($dateStart);
        $revenues = [];
        while($dateBegin->format('Ymd')<=$dateEnd->format('Ymd')) {
            $revenues[] = $this->getDayRevenue($dateBegin);
            $dateBegin->add(new DateInterval('P1D'));
        }
        return $revenues;
    }


    public function getOrderPerDayPerPeriod(DateTime $dateStart, DateTime $dateEnd)
    {
        $dateBegin = clone($dateStart);
        $revenues = [];
        while($dateBegin->format('Ymd')<=$dateEnd->format('Ymd')) {
            $revenues[] = $this->getNbOrdersDay($dateBegin);
            $dateBegin->add(new DateInterval('P1D'));
        }
        return $revenues;
    }



    public function getBackgroundColors($nb)
    {
        $colors = [
            "#733400",
            "#440099",
            "#72cd00",
            "#ff2200",
            "#00c4ff",
            "#f64600",
            "#00c1cc",
            "#995200",
            "#54e6a2",
            "#cea3a6",
            "#004400",
            "#e8fae3",
            "#004f55",
            "#ffcd76",
            "#006d82"
        ];

        return array_slice($colors, 0, $nb);



    }


    public function getAllSaleLineForPeriodByProduct(DateTime $dateStart, DateTime $dateEnd)
    {
        $saleOrderLines = $this->getAllSaleLineForPeriod($dateStart, $dateEnd);
        $products =[];
        foreach($saleOrderLines as $saleOrderLine) {
            $sku = $saleOrderLine->getProduct()->getSku();
            if(!array_key_exists($sku, $products)) {
                $products[$sku]=[
                    'product'=>$saleOrderLine->getProduct(),
                    'lines'=>[]
                ];
            }
            $products[$sku]['lines'][]=$saleOrderLine;
        }
        return $products;
    }


    public function getTopRevenueProducts()
    {
        $topProducts =$this->getTopByProducts($this->dateStartPeriod, $this->dateEndPeriod);
        usort($topProducts, function ($item1, $item2) {
            return $item2["revenue"] <=> $item1["revenue"];
        });
        return array_slice($topProducts, 0, 5);
        
    }


    public function getTopQuantityProducts()
    {
        $topProducts =$this->getTopByProducts($this->dateStartPeriod, $this->dateEndPeriod);
        usort($topProducts, function ($item1, $item2) {
            return $item2["quantity"] <=> $item1["quantity"];
        });
        return array_slice($topProducts, 0, 5);
    }


    public function getTopMarginProducts()
    {
        $topProducts =$this->getTopByProducts($this->dateStartPeriod, $this->dateEndPeriod);
        usort($topProducts, function ($item1, $item2) {
            return $item2["margin"] <=> $item1["margin"];
        });
        return array_slice($topProducts, 0, 5);
    }

    public function getTopMarginRateProducts()
    {
        $topProducts =$this->getTopByProducts($this->dateStartPeriod, $this->dateEndPeriod);
        usort($topProducts, function ($item1, $item2) {
            return $item2["marginRate"] <=> $item1["marginRate"];
        });
        return array_slice($topProducts, 0, 5);
    }


    public function getTopByProducts(DateTime $dateStart, DateTime $dateEnd)
    {
        $saleOrderLinesByProduct = $this->getAllSaleLineForPeriodByProduct($dateStart, $dateEnd);
        $productRevenues = [];
        foreach($saleOrderLinesByProduct as $saleOrderLine) {
            $revenue = 0;
            $quantity = 0;
            $cost = 0;
            foreach($saleOrderLine['lines'] as $line) {
                $revenue += $line->getTotalPrice();
                $cost += round($line->getTotalCost(), 2);
                $quantity += $line->getQuantity();
            }
            $productRevenues[]=[
                "revenue" => $revenue,
                "quantity" => $quantity,
                "margin"=> $revenue-$cost,
                "marginRate" => $this->calculateMarginRate($revenue, $cost),
                'sku'=> $saleOrderLine['product']->getSku(),
                "name" => $saleOrderLine['product']->getNameErp()
            ];
        }
        return $productRevenues;
    }



}
