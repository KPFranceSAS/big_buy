<?php

/*
 * This file is part of the umulmrum/holiday package.
 *
 * (c) Stefan Kruppa
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Helper\Utils;

use Umulmrum\Holiday\Constant\HolidayName;
use Umulmrum\Holiday\Constant\HolidayType;
use Umulmrum\Holiday\Model\Holiday;
use Umulmrum\Holiday\Model\HolidayList;
use Umulmrum\Holiday\Provider\CommonHolidaysTrait;
use Umulmrum\Holiday\Provider\HolidayProviderInterface;
use Umulmrum\Holiday\Provider\Religion\ChristianHolidaysTrait;

class KpsHolidaysProvider implements HolidayProviderInterface
{
    use ChristianHolidaysTrait;
    use CommonHolidaysTrait;

    /**
     * {@inheritdoc}
     */
    public function calculateHolidaysForYear(int $year): HolidayList
    {
        $holidays = new HolidayList();
        $holidays->add($this->getNewYear($year)); // 01/01
        $holidays->add($this->getEpiphany($year)); // 06/01
        
        $holidays->add($this->getGoodFriday($year)); // Easet friday
        $holidays->add($this->getEasterMonday($year)); // Easter monday
        $holidays->add($this->getLaborDay($year));  // 01/05
        $holidays->add($this->getSaintJamesDay($year)); // 24/06
        
        $holidays->add($this->getAssumptionDay($year)); // 15/08
        $holidays->add($this->getSpanishNationalDay($year)); // 12/10
        $holidays->add($this->getAllSaintsDay($year)); // 01/11
        $holidays->add($this->getSpanishConstitutionDay($year)); // 06/12
        $holidays->add($this->getValenciaSpanishConstitutionDay($year)); // 06/12
        $holidays->add($this->getChristmasDay($year)); //25/12
        $holidays->add($this->getSaintEstebanDay($year)); //26/12

        return $holidays;
    }


    private function getSaintJamesDay(int $year, int $additionalType = HolidayType::OTHER): Holiday
    {
        return Holiday::create('', "{$year}-06-24", HolidayType::OFFICIAL | $additionalType);
    }


    private function getSaintEstebanDay(int $year, int $additionalType = HolidayType::OTHER): Holiday
    {
        return Holiday::create('', "{$year}-12-26", HolidayType::OFFICIAL | $additionalType);
    }


    private function getSpanishNationalDay(int $year, int $additionalType = HolidayType::OTHER): Holiday
    {
        return Holiday::create(HolidayName::SPANISH_NATIONAL_DAY, "{$year}-10-12", HolidayType::OFFICIAL | $additionalType);
    }

    private function getSpanishConstitutionDay(int $year, int $additionalType = HolidayType::OTHER): Holiday
    {
        return Holiday::create(HolidayName::CONSTITUTION_DAY, "{$year}-12-06", HolidayType::OFFICIAL | $additionalType);
    }

    private function getValenciaSpanishConstitutionDay(int $year, int $additionalType = HolidayType::OTHER): Holiday
    {
        return Holiday::create(HolidayName::CONSTITUTION_DAY, "{$year}-12-08", HolidayType::OFFICIAL | $additionalType);
    }
}
