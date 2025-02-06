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

        $calendar = [];

        foreach ($moons as $moon) {
            $moonStart = $startDate->copy()->addDays($moon['offset']);
            for ($day = 1; $day <= 28; $day++) {
                $currentDate = $moonStart->copy()->addDays($day - 1);
                $calendar[] = [
                    'moon' => $moon['name'],
                    'moon_latin' => $moon['latin'],
                    'moon_number' => $moon['number'],
                    'moon_roman' => $moon['roman'],
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
