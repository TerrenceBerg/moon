<?php

namespace Tuna976\CustomCalendar;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Tuna976\CustomCalendar\Models\SolarEvent;
use Tuna976\CustomCalendar\Models\NOAATideForecast;

class CustomCalendar
{
    protected $year;
    protected $stationId;

    public function __construct($year = null, $stationId = null)
    {
        $this->year = $year ?: Carbon::now()->year;
        $this->stationId = $stationId;
    }

    public function generateCalendar($year = null, $stationId = null)
    {
        $currentYear = $year ?? Carbon::now()->year;
        $stationId = $stationId ?? $this->stationId;
        $station=NOAAStation::where('station_id', $stationId)->first();
        $yearRange = range($currentYear - 0, $currentYear + 1);
        $calendarData = [];

        // Fetch solar events
        $solarEvents = SolarEvent::whereIn('year', $yearRange)->get()->keyBy('year');

        // Fetch tide and NOAA data for the station
        $noaaData = NOAATideForecast::where('station_id', $station->id)
            ->whereIn('year', $yearRange)->get()->keyBy('year');


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
                continue;
            }

            $vernalEquinox = Carbon::parse($solarEvents[$year]->march_equinox);
            $months = [];

            foreach ($moons as $moon) {
                $monthStart = $vernalEquinox->copy()->addDays($moon['offset']);
                $monthDays = [];

                for ($i = 0; $i < 28; $i++) {
                    $dayDate = $monthStart->copy()->addDays($i);
                    $dayTideData = $noaaData[$year]->where('date', $dayDate->toDateString())->first();

                    $monthDays[] = [
                        'date' => $dayDate->toDateString(),
                        'day_of_week' => $dayDate->format('l'),
                        'julian_day' => $dayDate->dayOfYear,
                        'gregorian_date' => $dayDate->format('M j, Y'),
                        'moon_phase' => $this->getMoonPhase($dayDate),
//                        'sun_data' => $this->getSunriseSunset($station->longitue,$station->latitue,$dayDate),
                        'tide_data' => $dayTideData ? [
                            'high_tide_time' => $dayTideData->high_tide_time,
                            'high_tide_level' => $dayTideData->high_tide_level,
                            'low_tide_time' => $dayTideData->low_tide_time,
                            'low_tide_level' => $dayTideData->low_tide_level,
                            'water_temperature' => $dayTideData->water_temperature,
                            'sunrise' => $dayTideData->sunrise,
                            'sunset' => $dayTideData->sunset
                        ] : null,
                    ];
                }

                $months[] = [
                    'name' => "{$moon['name']}",
                    'start_date' => $monthStart->toDateString(),
                    'end_date' => $monthStart->copy()->addDays(27)->toDateString(),
                    'start_day_of_week' => $monthStart->format('l'),
                    'days' => $monthDays,
                ];
            }

            $calendarData[$year] = [
                'solar_events' => [
                    'march_equinox' => Carbon::parse($solarEvents[$year]->march_equinox)->format('d-m-Y H:i:s'),
                    'june_solstice' => Carbon::parse($solarEvents[$year]->june_solstice)->format('d-m-Y H:i:s'),
                    'september_equinox' => Carbon::parse($solarEvents[$year]->september_equinox)->format('d-m-Y H:i:s'),
                    'december_solstice' => Carbon::parse($solarEvents[$year]->december_solstice)->format('d-m-Y H:i:s'),
                ],
                'is_leap_year' => ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0),
                'months' => $months,
            ];
        }
        return $calendarData;
    }


    private function getMoonPhase($date)
    {
        $synodicMonth = 29.53058867;
        $knownNewMoon = Carbon::create(2000, 1, 6, 18, 14, 0); // Reference new moon
        $daysSinceNewMoon = $knownNewMoon->floatDiffInDays($date);

        $moonAge = fmod($daysSinceNewMoon, $synodicMonth);
        $phaseIndex = round(($moonAge / $synodicMonth) * 8) % 8;

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

//    private function getSunriseSunset($latitude, $longitude, $date)
//    {
//        try {
//            $formattedDate = Carbon::parse($date)->format('Y-m-d');
//
//            $response = Http::get("https://api.sunrise-sunset.org/json", [
//                'lat' => $latitude,
//                'lng' => $longitude,
//                'date' => $formattedDate,
//                'formatted' => 0 // Get times in UTC
//            ]);
//
//            if ($response->successful()) {
//                $data = $response->json();
//                $sunrise = Carbon::parse($data['results']['sunrise'])->setTimezone('America/Los_Angeles')->format('H:i');
//                $sunset = Carbon::parse($data['results']['sunset'])->setTimezone('America/Los_Angeles')->format('H:i');
//
//                return [
//                    'sunrise' => $sunrise,
//                    'sunset' => $sunset
//                ];
//            } else {
//                return ['sunrise' => 'N/A', 'sunset' => 'N/A'];
//            }
//        } catch (\Exception $e) {
//            \Log::error("Error fetching sunrise/sunset: " . $e->getMessage());
//            return ['sunrise' => 'N/A', 'sunset' => 'N/A'];
//        }
//    }
}
