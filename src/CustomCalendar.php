<?php

namespace Tuna976\CustomCalendar;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tuna976\CustomCalendar\Models\SolarEvent;

class CustomCalendar
{
    protected $year;

    public function __construct($year = null)
    {
        $this->year = $year ?: Carbon::now()->year;
    }

    public function generateCalendar($year = null)
    {
        ini_set('memory_limit', '512M');
        $currentYear = $year ?? Carbon::now()->year;
        $yearRange = range($currentYear - 5, $currentYear + 5);
        $calendarData = [];

        // Fetch solar events for all required years
        $solarEvents = SolarEvent::whereIn('year', $yearRange)->get()->keyBy('year');

        $moons = [
            ['name' => 'Magnetic Moon', 'latin' => 'Luna Magnetica', 'roman' => 'Unus', 'offset' => 0],
            ['name' => 'Lunar Moon', 'latin' => 'Luna Lunaris', 'roman' => 'Duo', 'offset' => 28],
            ['name' => 'Electric Moon', 'latin' => 'Luna Electrica', 'roman' => 'Tres', 'offset' => 56],
            ['name' => 'Self-Existing Moon', 'latin' => 'Luna Sui Existentia', 'roman' => 'Quattuor', 'offset' => 84],
            ['name' => 'Overtone Moon', 'latin' => 'Luna Superior', 'roman' => 'Quinque', 'offset' => 112],
            ['name' => 'Rhythmic Moon', 'latin' => 'Luna Rhythmica', 'roman' => 'Sex', 'offset' => 140],
            ['name' => 'Resonant Moon', 'latin' => 'Luna Resonans', 'roman' => 'Septem', 'offset' => 168],
            ['name' => 'Galactic Moon', 'latin' => 'Luna Galactica', 'roman' => 'Octo', 'offset' => 196],
            ['name' => 'Solar Moon', 'latin' => 'Luna Solaris', 'roman' => 'Novem', 'offset' => 224],
            ['name' => 'Planetary Moon', 'latin' => 'Luna Planetaria', 'roman' => 'Decem', 'offset' => 252],
            ['name' => 'Spectral Moon', 'latin' => 'Luna Spectralis', 'roman' => 'Undecim', 'offset' => 280],
            ['name' => 'Crystal Moon', 'latin' => 'Luna Crystallina', 'roman' => 'Duodecim', 'offset' => 308],
            ['name' => 'Cosmic Moon', 'latin' => 'Luna Cosmica', 'roman' => 'Tredecim', 'offset' => 336],
        ];

        foreach ($yearRange as $year) {
            if (!isset($solarEvents[$year])) {
                continue; // Skip years without solar event data
            }

            $vernalEquinox = Carbon::parse($solarEvents[$year]->march_equinox);
            $months = [];

            foreach ($moons as $moon) {
                $monthStart = $vernalEquinox->copy()->addDays($moon['offset']);
                $monthDays = [];

                for ($i = 0; $i < 28; $i++) {
                    $dayDate = $monthStart->copy()->addDays($i);
                    $monthDays[] = [
                        'date' => $dayDate->toDateString(),
                        'day_of_week' => $dayDate->format('l'),
                        'julian_day' => $dayDate->dayOfYear,
                        'gregorian_date' => $dayDate->format('d-m-Y'),
                        'moon_phase' => $this->getMoonPhase($dayDate),
                    ];
                }

                $months[] = [
                    'name' => "{$moon['name']} - {$moon['roman']} ({$moon['latin']})",
                    'start_date' => $monthStart->toDateString(),
                    'end_date' => $monthStart->copy()->addDays(27)->toDateString(),
                    'start_day_of_week' => $monthStart->format('l'),
                    'days' => $monthDays,
                ];
            }

            // Assign solar events at the **year level**
            $calendarData[$year] = [
                'solar_events' => [
                    'march_equinox' => Carbon::parse($solarEvents[$year]->march_equinox)->format('d-m-Y H:i:s'),
                    'june_solstice' => Carbon::parse($solarEvents[$year]->june_solstice)->format('d-m-Y H:i:s'),
                    'september_equinox' => Carbon::parse($solarEvents[$year]->september_equinox)->format('d-m-Y H:i:s'),
                    'december_solstice' => Carbon::parse($solarEvents[$year]->december_solstice)->format('d-m-Y H:i:s'),
                ],
                'months' => $months,
            ];
        }

        return $calendarData;
    }

    private function getMoonPhase($date)
    {
        $baseDate = Carbon::create(2000, 1, 6, 18, 14, 0);
        $daysSinceBase = $date->diffInDays($baseDate);
        $synodicMonth = 29.53058867;

        $moonAge = fmod($daysSinceBase, $synodicMonth);
        $phaseIndex = floor(($moonAge / $synodicMonth) * 8) % 8;

        $moonPhases = [
            0 => 'New Moon 🌑',
            1 => 'Waxing Crescent 🌒',
            2 => 'First Quarter 🌓',
            3 => 'Waxing Gibbous 🌔',
            4 => 'Full Moon 🌕',
            5 => 'Waning Gibbous 🌖',
            6 => 'Last Quarter 🌗',
            7 => 'Waning Crescent 🌘'
        ];

        return $moonPhases[$phaseIndex] ?? null;
    }
}
