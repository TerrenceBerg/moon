<?php
namespace Tuna976\Helpers;

use Carbon\Carbon;
use Alkoumi\LaravelHijriDate\Hijri;

class MoonCalendarHelper
{
    public static function generateCalendar($year = null)
    {
        $year = $year ?? Carbon::now()->year;
        $startDate = Carbon::create($year, 7, 26); // 13 Moon Calendar starts on July 26

        $moons = [
            ['name' => 'Magnetic Moon', 'number' => 1, 'offset' => 0],
            ['name' => 'Lunar Moon', 'number' => 2, 'offset' => 28],
            ['name' => 'Electric Moon', 'number' => 3, 'offset' => 56],
            ['name' => 'Self-Existing Moon', 'number' => 4, 'offset' => 84],
            ['name' => 'Overtone Moon', 'number' => 5, 'offset' => 112],
            ['name' => 'Rhythmic Moon', 'number' => 6, 'offset' => 140],
            ['name' => 'Resonant Moon', 'number' => 7, 'offset' => 168],
            ['name' => 'Galactic Moon', 'number' => 8, 'offset' => 196],
            ['name' => 'Solar Moon', 'number' => 9, 'offset' => 224],
            ['name' => 'Planetary Moon', 'number' => 10, 'offset' => 252],
            ['name' => 'Spectral Moon', 'number' => 11, 'offset' => 280],
            ['name' => 'Crystal Moon', 'number' => 12, 'offset' => 308],
            ['name' => 'Cosmic Moon', 'number' => 13, 'offset' => 336],
        ];

        $calendar = [];

        foreach ($moons as $moon) {
            $moonStart = $startDate->copy()->addDays($moon['offset']);
            for ($day = 1; $day <= 28; $day++) {
                $currentDate = $moonStart->copy()->addDays($day - 1);
                $calendar[] = [
                    'moon' => $moon['name'],
                    'moon_number' => $moon['number'],
                    'day' => $day,
                    'gregorian' => $currentDate->toDateString(),
                    'hijri' => Hijri::Date('Y-m-d', $currentDate->timestamp),
                    'is_today' => $currentDate->isToday(),
                ];
            }
        }

        return [
            'year' => $year,
            'calendar' => $calendar,
            'previous_year' => $year - 1,
            'next_year' => $year + 1,
        ];
    }
}
