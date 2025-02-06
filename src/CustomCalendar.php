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
        $year = $this->year ?? Carbon::now()->year;
        $startDate = Carbon::create($year, 7, 26);

        $moons = [
            ['name' => 'Magnetic Moon', 'latin' => 'Luna Magnetica', 'number' => 1, 'roman' => 'Unus', 'offset' => 0],
            ['name' => 'Lunar Moon', 'latin' => 'Luna Lunaris', 'number' => 2, 'roman' => 'Duo', 'offset' => 28],
            ['name' => 'Electric Moon', 'latin' => 'Luna Electrica', 'number' => 3, 'roman' => 'Tres', 'offset' => 56],
            ['name' => 'Self-Existing Moon', 'latin' => 'Luna Sui Existentia', 'number' => 4, 'roman' => 'Quattuor', 'offset' => 84],
            ['name' => 'Overtone Moon', 'latin' => 'Luna Superior', 'number' => 5, 'roman' => 'Quinque', 'offset' => 112],
            ['name' => 'Rhythmic Moon', 'latin' => 'Luna Rhythmica', 'number' => 6, 'roman' => 'Sex', 'offset' => 140],
            ['name' => 'Resonant Moon', 'latin' => 'Luna Resonans', 'number' => 7, 'roman' => 'Septem', 'offset' => 168],
            ['name' => 'Galactic Moon', 'latin' => 'Luna Galactica', 'number' => 8, 'roman' => 'Octo', 'offset' => 196],
            ['name' => 'Solar Moon', 'latin' => 'Luna Solaris', 'number' => 9, 'roman' => 'Novem', 'offset' => 224],
            ['name' => 'Planetary Moon', 'latin' => 'Luna Planetaria', 'number' => 10, 'roman' => 'Decem', 'offset' => 252],
            ['name' => 'Spectral Moon', 'latin' => 'Luna Spectralis', 'number' => 11, 'roman' => 'Undecim', 'offset' => 280],
            ['name' => 'Crystal Moon', 'latin' => 'Luna Crystallina', 'number' => 12, 'roman' => 'Duodecim', 'offset' => 308],
            ['name' => 'Cosmic Moon', 'latin' => 'Luna Cosmica', 'number' => 13, 'roman' => 'Tredecim', 'offset' => 336],
        ];


        $months = [];
        foreach ($moons as $moon) {
            $monthStart = $startDate->copy()->addDays($moon['offset']);
            $months[] = [
                'name' => $moon['name'].' - '.$moon['roman'],
                'start_date' => $monthStart->toDateString(),
                'end_date' => $monthStart->copy()->addDays(27)->toDateString(),
            ];
        }

        $solunarData = [];
        $tideData = [];
        $moonPhases = [];
        foreach ($months as $month) {
            $solunarData[$month['name']] = ['best_fishing_time' => '06:30 AM - 08:30 AM'];
            $tideData[$month['name']] = ['high_tide' => '02:45 PM', 'low_tide' => '08:15 AM'];
            $moonPhases[$month['name']] = ['new_moon' => '2025-01-10', 'full_moon' => '2025-01-24'];
        }

        return [
            $year => [
                'months' => $months,
                'solunar' => $solunarData,
                'tides' => $tideData,
                'moon_phases' => $moonPhases,
            ],
        ];
    }
}
