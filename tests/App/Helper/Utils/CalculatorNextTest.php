<?php

namespace App\Tests\App\Helper\Utils;

use App\Helper\Utils\CalculatorNext;
use DateTime;
use PHPUnit\Framework\TestCase;

class CalculatorNextTest extends TestCase
{
    public function testNormal(): void
    {
        $dateTime = new DateTime("2023-08-28 12:00");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '09:00');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2023-08-29 09:00');
    }


    public function testNormalHalf(): void
    {
        $dateTime = new DateTime("2023-08-28 12:00");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '08:30');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2023-08-29 08:30');
    }


    public function testDayAfter(): void
    {
        $dateTime = new DateTime("2023-08-28 23:00");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '09:00');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2023-08-29 09:00');
    }


    public function testDayASame(): void
    {
        $dateTime = new DateTime("2023-08-28 08:59");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '09:00');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2023-08-28 09:00');
    }

    public function testDayAt900Same(): void
    {
        $dateTime = new DateTime("2023-08-28 09:00");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '09:00');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2023-08-28 09:00');
    }


    public function testDayAt901Same(): void
    {
        $dateTime = new DateTime("2023-08-28 09:01");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '09:00');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2023-08-29 09:00');
    }


    public function testAfterWeekend(): void
    {
        $dateTime = new DateTime("2023-08-25 10:00");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '09:00');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2023-08-28 09:00');
    }


    public function testFirstJAnauary(): void
    {
        $dateTime = new DateTime("2023-12-29 10:00");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '09:00');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2024-01-02 09:00');
    }


    public function testChristmasDay(): void
    {
        $dateTime = new DateTime("2023-12-22 10:00");
        $nextDelivery = CalculatorNext::getNextDelivery($dateTime, '09:00');
        $this->assertEquals($nextDelivery->format('Y-m-d H:i'), '2023-12-27 09:00');
    }
}
