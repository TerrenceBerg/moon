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

    public function generateCalendar($year = null)
    {
        ini_set('memory_limit', '512M');
        $currentYear = $year ?? Carbon::now()->year;
        $yearRange = range($currentYear, $currentYear + 5);
        $calendarData = [];

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
            $vernalEquinox = Carbon::create($year, 3, ($year % 4 === 0 ? 21 : 20));
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
                    'name' => $moon['name'] . ' - ' . $moon['roman'],
                    'start_date' => $monthStart->toDateString(),
                    'end_date' => $monthStart->copy()->addDays(27)->toDateString(),
                    'start_day_of_week' => $monthStart->format('l'),
                    'days' => $monthDays,
                ];
            }

            $calendarData[$year] = ['months' => $months];
        }

        return $calendarData;
    }

    private function getMoonPhase($date)
    {
        $baseDate = Carbon::create(2000, 1, 6);
        $daysSinceBase = $date->diffInDays($baseDate);
        $synodicMonth = 29.53058867;

        $phaseIndex = round(fmod($daysSinceBase / $synodicMonth, 1) * 8) % 8;

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
