<?php

namespace Tuna976\CustomCalendar;

use Carbon\Carbon;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Tuna976\CustomCalendar\Models\SolarEvent;
use Tuna976\CustomCalendar\Models\NOAATideForecast;

class CustomCalendar
{
    protected $year;
    protected $stationId;

    public function __construct($year = null, $stationId = null)
    {
        $this->year = $year ?? Carbon::now()->year;
        $this->stationId = $stationId;
    }

    public function generateCalendar($year = null, $stationId = null)
    {
        $currentYear = $year ?? Carbon::now()->year;
        $stationId = $stationId ?? $this->stationId;
        $station = NOAAStation::where('id', $stationId)->firstOrFail();
        $yearRange = [$currentYear-1, $currentYear];
        $calendarData = [];

        $solarEvents = SolarEvent::whereIn('year', $yearRange)->pluck('march_equinox', 'year');
        $noaaData = NOAATideForecast::where('station_id', $station->id)
            ->whereIn('year', $yearRange)
            ->get()
            ->groupBy('date');

        $moons = $this->getMoonPhases();

        foreach ($yearRange as $year) {
            if (!isset($solarEvents[$year])) {
                continue;
            }

            $vernalEquinox = Carbon::parse($solarEvents[$year]);
            $months = [];

            foreach ($moons as $moon) {
                $monthStart = $vernalEquinox->copy()->addDays($moon['offset']);
                $monthDays = [];

                for ($i = 0; $i < 28; $i++) {
                    $dayDate = $monthStart->copy()->addDays($i)->toDateString();
                    $dayTideData = $noaaData[$dayDate][0] ?? null;

                    $monthDays[] = [
                        'date' => $dayDate,
                        'day_of_week' => Carbon::parse($dayDate)->format('l'),
                        'julian_day' => Carbon::parse($dayDate)->dayOfYear,
                        'gregorian_date' => Carbon::parse($dayDate)->format('M j, Y'),
                        'moon_phase' => $this->getMoonPhase($dayDate),
                        'is_today' => Carbon::parse($dayDate)->isToday(),
                        'tide_data' => $dayTideData ? $this->formatTideData($dayTideData) : null,
                    ];
                }

                $months[] = [
                    'name' => $moon['name'],
                    'start_date' => $monthStart->toDateString(),
                    'end_date' => $monthStart->copy()->addDays(27)->toDateString(),
                    'start_day_of_week' => $monthStart->format('l'),
                    'days' => $monthDays,
                ];
            }

            $calendarData[$year] = [
                'solar_events' => $this->formatSolarEvents($solarEvents[$year]),
                'is_leap_year' => $this->isLeapYear($year),
                'months' => $months,
            ];
        }

        return $calendarData;
    }

    private function getMoonPhases()
    {
        return [
            ['name' => 'Magnetic Moon', 'offset' => 0],
            ['name' => 'Lunar Moon', 'offset' => 28],
            ['name' => 'Electric Moon', 'offset' => 56],
            ['name' => 'Self-Existing Moon', 'offset' => 84],
            ['name' => 'Overtone Moon', 'offset' => 112],
            ['name' => 'Rhythmic Moon', 'offset' => 140],
            ['name' => 'Resonant Moon', 'offset' => 168],
            ['name' => 'Galactic Moon', 'offset' => 196],
            ['name' => 'Solar Moon', 'offset' => 224],
            ['name' => 'Planetary Moon', 'offset' => 252],
            ['name' => 'Spectral Moon', 'offset' => 280],
            ['name' => 'Crystal Moon', 'offset' => 308],
            ['name' => 'Cosmic Moon', 'offset' => 336],
        ];
    }

    private function getMoonPhase($date)
    {
        $synodicMonth = 29.53058867;
        $knownNewMoon = Carbon::create(2000, 1, 6, 18, 14, 0);
        $daysSinceNewMoon = $knownNewMoon->floatDiffInDays(Carbon::parse($date));

        $moonPhases = [
            'New Moon 🌑', 'Waxing Crescent 🌒', 'First Quarter 🌓',
            'Waxing Gibbous 🌔', 'Full Moon 🌕', 'Waning Gibbous 🌖',
            'Last Quarter 🌗', 'Waning Crescent 🌘'
        ];

        return $moonPhases[(int)round(($daysSinceNewMoon % $synodicMonth) / $synodicMonth * 8) % 8] ?? null;
    }

    private function formatSolarEvents($date)
    {
        $carbonDate = Carbon::parse($date);
        return [
            'march_equinox' => $carbonDate->format('d-m-Y H:i:s'),
            'june_solstice' => $carbonDate->addMonths(3)->format('d-m-Y H:i:s'),
            'september_equinox' => $carbonDate->addMonths(6)->format('d-m-Y H:i:s'),
            'december_solstice' => $carbonDate->addMonths(9)->format('d-m-Y H:i:s'),
        ];
    }

    private function isLeapYear($year)
    {
        return ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0);
    }

    private function formatTideData($data)
    {
        return [
            'high_tide_time' => $data->high_tide_time,
            'high_tide_level' => $data->high_tide_level,
            'low_tide_time' => $data->low_tide_time,
            'low_tide_level' => $data->low_tide_level,
            'water_temperature' => $data->water_temperature,
            'sunrise' => $data->sunrise,
            'sunset' => $data->sunset
        ];
    }
}
