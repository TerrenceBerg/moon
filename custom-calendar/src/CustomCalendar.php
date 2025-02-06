<?php

namespace Tuna976\CustomCalendar;

use Carbon\Carbon;

class CustomCalendar
{
    protected $year;

    public function __construct($year = null)
    {
        $this->year = $year ?: Carbon::now()->year;
    }

    public function generate()
    {
        $calendar = [];
        $startDate = Carbon::create($this->year, 7, 26); // Start from July 26

        for ($month = 1; $month <= 13; $month++) {
            $calendar[$month] = [];

            for ($day = 1; $day <= 28; $day++) {
                $currentDate = $startDate->copy()->addDays(($month - 1) * 28 + ($day - 1));
                $gregorian = $currentDate->format('Y-m-d');

                $calendar[$month][$day] = [
                    'gregorian' => $gregorian,
                    'month' => $month,
                    'day' => $day,
                    'is_today' => $currentDate->isToday()
                ];
            }
        }

        return $calendar;
    }
}
