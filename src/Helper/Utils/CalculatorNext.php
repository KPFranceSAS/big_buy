<?php

namespace App\Helper\Utils;

use App\Helper\Utils\KpsHolidaysProvider;
use DateTime;
use DateTimeInterface;
use Umulmrum\Holiday\HolidayCalculator;

/**
 * Class Calculator
 */
class CalculatorNext
{

    public static function getNextDelivery(DateTime $dateStart, $startHour): DateTime
    {
        $calculator = new CalculatorNext();
        $calculator->setStartDate($dateStart);
        $holidays = self::getHolidaysFrom($dateStart);
        $calculator->setFreeWeekDays([
            CalculatorNext::SATURDAY,
            CalculatorNext::SUNDAY
        ]);


        if($calculator->getDate()->format('H:i') > $startHour) {
            $calculator->getDate()->modify('+1 day');
        }

        $startHourArray = explode(':', $startHour, 2);

        $calculator->getDate()->setTime((int)$startHourArray[0], (int)$startHourArray[1], 0, 0);

        $calculator->setHolidays($holidays);
        $calculator->gotToNext();
        return  $calculator->getDate();
    }



    const MONDAY    = 1;
    const TUESDAY   = 2;
    const WEDNESDAY = 3;
    const THURSDAY  = 4;
    const FRIDAY    = 5;
    const SATURDAY  = 6;
    const SUNDAY    = 7;

    const WEEK_DAY_FORMAT = 'N';
    const HOLIDAY_FORMAT  = 'Y-m-d';
    const FREE_DAY_FORMAT = 'Y-m-d';

    /** @var DateTime */
    private $date;

    /** @var DateTime[] */
    private $holidays = array();

    /** @var DateTime[] */
    private $freeDays = array();

    /** @var int[] */
    private $freeWeekDays = array();




    public static function getHolidaysFrom(DateTimeInterface $date)
    {
        $holidayDateTime = [];
        $years = [];
        $year1 = $date->format('Y');
        $year2 = $year1 + 1;

        $years[] = (int)$year1;
        $years[] = (int)$year2;

        $holidayCalculator = new HolidayCalculator();
        $holidays = $holidayCalculator->calculate(KpsHolidaysProvider::class, $years);
        foreach ($holidays as $holiday) {
            $holidayDateTime[] = $holiday->getDate();
        }
        return $holidayDateTime;
    }




    /**
     * @param DateTime $startDate Date to start calculations from
     *
     * @return $this
     */
    public function setStartDate(DateTimeInterface $startDate)
    {
        // Use clone so parameter is not passed as a reference.
        // If not, it can brake caller method by changing $startDate parameter while changing it here.

        $this->date = clone $startDate;

        return $this;
    }

    /**
     * @param DateTime[] $holidays Array of holidays that repeats each year. (Only month and date is used to match).
     *
     * @return $this
     */
    public function setHolidays(array $holidays)
    {
        $this->holidays = $holidays;

        return $this;
    }

    /**
     * @return DateTime[]
     */
    private function getHolidays()
    {
        return $this->holidays;
    }

    /**
     * @param DateTime[] $freeDays Array of free days that dose not repeat.
     *
     * @return $this
     */
    public function setFreeDays(array $freeDays)
    {
        $this->freeDays = $freeDays;

        return $this;
    }

    /**
     * @return DateTime[]
     */
    private function getFreeDays()
    {
        return $this->freeDays;
    }

    /**
     * @param int[] $freeWeekDays Array of days of the week which are not business days.
     *
     * @return $this
     */
    public function setFreeWeekDays(array $freeWeekDays)
    {
        $this->freeWeekDays = $freeWeekDays;

        return $this;
    }

    /**
     * @return int[]
     */
    private function getFreeWeekDays()
    {
        if (count($this->freeWeekDays) >= 7) {
            throw new \InvalidArgumentException('Too many non business days provided');
        }

        return $this->freeWeekDays;
    }

    /**
     * @param int $howManyDays
     *
     * @return $this
     */
    public function addBusinessDays($howManyDays)
    {
        $iterator = 0;
        while ($iterator < $howManyDays) {
            $this->getDate()->modify('+1 day');

            if ($this->isBusinessDay($this->getDate())) {
                $iterator++;
            }
        }

        return $this;
    }

    /**
     * @param int $howManyDays
     *
     * @return $this
     */
    public function removeBusinessDays($howManyDays)
    {
        $iterator = 0;
        while ($iterator < $howManyDays) {
            $this->getDate()->modify('-1 day');
            if ($this->isBusinessDay($this->getDate())) {
                $iterator++;
            }
        }

        return $this;
    }




    /**
     * @return DateTime
     */
    public function getDate()
    {
        if ($this->date === null) {
            $this->date = new DateTime();
        }

        return $this->date;
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isBusinessDay(DateTimeInterface $date)
    {
        if ($this->isFreeWeekDayDay($date)) {
            return false;
        }

        if ($this->isHoliday($date)) {
            return false;
        }
        return !$this->isFreeDay($date);
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isFreeWeekDayDay(DateTimeInterface $date)
    {
        $currentWeekDay = (int)$date->format(self::WEEK_DAY_FORMAT);
        return in_array($currentWeekDay, $this->getFreeWeekDays());
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isHoliday(DateTimeInterface $date)
    {
        $holidayFormatValue = $date->format(self::HOLIDAY_FORMAT);
        foreach ($this->getHolidays() as $holiday) {
            if ($holidayFormatValue === $holiday->format(self::HOLIDAY_FORMAT)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isFreeDay(DateTimeInterface $date)
    {
        $freeDayFormatValue = $date->format(self::FREE_DAY_FORMAT);
        foreach ($this->getFreeDays() as $freeDay) {
            if ($freeDayFormatValue === $freeDay->format(self::FREE_DAY_FORMAT)) {
                return true;
            }
        }

        return false;
    }


    public function gotToNext()
    {
        while (!$this->isBusinessDay($this->getDate())) {
            $this->getDate()->modify('+1 day');
        }

    }
}
