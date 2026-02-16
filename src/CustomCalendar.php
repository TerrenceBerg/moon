<?php

namespace Tuna976\CustomCalendar;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Tuna976\CustomCalendar\Models\SolarEvent;
use Tuna976\CustomCalendar\Models\NOAATideForecast;

class CustomCalendar
{
    protected $year;
    protected $stationId;
    protected $selectedStation;

    public function __construct($year = null, $stationId = null)
    {
        $this->year = $year ?? Carbon::now()->year;
        $this->stationId = $stationId;

        if (isset($stationId)) {
            $this->selectedStation = NOAAStation::find($this->stationId);
        }
    }
    
    public function generateCalendar($year = null, $stationId = null)
    {
        ini_set('max_execution_time', 300);

        $selectedYear = (int)($year ?? Carbon::now()->year);
        $stationId = $stationId ?? $this->stationId;

        $station = NOAAStation::where('id', $stationId)->firstOrFail();

        // This is the key change for "Option 2"
        $startEquinoxYear = $selectedYear - 1;

        // Need equinox for start year (and optionally selectedYear if your DB logic ever needs it)
        $solarEvents = SolarEvent::whereIn('year', [$startEquinoxYear, $selectedYear])
            ->pluck('march_equinox', 'year');

        if (!isset($solarEvents[$startEquinoxYear])) {
            // No equinox data for start year => can't generate
            return [];
        }

        $vernalEquinox = Carbon::parse($solarEvents[$startEquinoxYear])->startOfDay();

        // 13 moons * 28 days = 364 days total. (day 0..363)
        $rangeStart = $vernalEquinox->copy()->toDateString();
        $rangeEnd   = $vernalEquinox->copy()->addDays(363)->toDateString();

        // Pull NOAA rows only for this window (covers Feb of selectedYear properly)
        $noaaData = NOAATideForecast::where('station_id', $station->id)
            ->whereBetween('date', [$rangeStart, $rangeEnd])
            ->get()
            ->groupBy('date');

        $moons = $this->getMoonPhases();

        $months = [];

        foreach ($moons as $moon) {
            $monthStart = $vernalEquinox->copy()->addDays((int)$moon['offset']);
            $monthDays = [];

            for ($i = 0; $i < 28; $i++) {
                $day = $monthStart->copy()->addDays($i);
                $dayDate = $day->toDateString();

                $cacheKey = "solunar_rating_{$station->latitude}_{$station->longitude}_{$dayDate}";
                $solunarData = Cache::get($cacheKey);

                if (!$solunarData) {
                    $solunarData = $this->getSolunarData($station->latitude, $station->longitude, $dayDate);
                    // getSolunarData already caches for 30 days
                }

                $dayTideData = $noaaData[$dayDate][0] ?? null;

                $monthDays[] = [
                    'date' => $dayDate,
                    'day_of_week' => $day->format('l'),
                    'julian_day' => $day->dayOfYear,
                    'gregorian_date' => $day->format('M j, Y'),
                    'is_today' => $day->isToday(),
                    'moon_phase' => $this->getMoonPhase($dayDate),
                    'moon_data' => $this->getMoonData($dayDate),
                    'tide_data' => $dayTideData ? $this->formatTideData($dayTideData) : null,
                    'solunar_rating' => $solunarData['calculatedRating'] ?? null,

                    // Optional helpers if you ever want to show "Gregorian month headers"
                    'gregorian_month' => $day->format('F'),
                    'gregorian_year' => (int)$day->format('Y'),
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

        // Return under the SELECTED year label (e.g., 2026)
        return [
            $selectedYear => [
                'is_leap_year' => $this->isLeapYear($selectedYear),
                'months' => $months,
                'range_start' => $rangeStart,
                'range_end' => $rangeEnd,
                'equinox_year' => $startEquinoxYear,
            ],
        ];
    }

    /**
     * Generate a single 13-moon month (index 0..12) for the selectedYear window.
     * selectedYear=2026 => equinoxYear=2025
     */
    public function generateCalendarMonth($year, $stationId, $monthIndex)
    {
        ini_set('max_execution_time', 120);

        $selectedYear = (int)$year;
        $station = NOAAStation::where('id', $stationId)->firstOrFail();

        $startEquinoxYear = $selectedYear - 1;

        $solarEvents = SolarEvent::whereIn('year', [$startEquinoxYear, $selectedYear])
            ->pluck('march_equinox', 'year');

        if (!isset($solarEvents[$startEquinoxYear])) {
            throw new \Exception("No solar event data (march_equinox) for year {$startEquinoxYear}.");
        }

        $vernalEquinox = Carbon::parse($solarEvents[$startEquinoxYear])->startOfDay();
        $moons = $this->getMoonPhases();

        if (!isset($moons[$monthIndex])) {
            throw new \Exception("Invalid month index {$monthIndex}.");
        }

        $moon = $moons[$monthIndex];
        $monthStart = $vernalEquinox->copy()->addDays((int)$moon['offset']);

        // Pull NOAA only for this 28-day month window
        $start = $monthStart->copy()->toDateString();
        $end   = $monthStart->copy()->addDays(27)->toDateString();

        $noaaData = NOAATideForecast::where('station_id', $station->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('date');

        $monthDays = [];

        for ($i = 0; $i < 28; $i++) {
            $day = $monthStart->copy()->addDays($i);
            $dayDate = $day->toDateString();

            $solunarData = $this->getSolunarData($station->latitude, $station->longitude, $dayDate);
            $dayTideData = $noaaData[$dayDate][0] ?? null;

            $monthDays[] = [
                'date' => $dayDate,
                'day_of_week' => $day->format('l'),
                'julian_day' => $day->dayOfYear,
                'gregorian_date' => $day->format('M j, Y'),
                'is_today' => $day->isToday(),
                'moon_phase' => $this->getMoonPhase($dayDate),
                'moon_data' => $this->getMoonData($dayDate),
                'tide_data' => $dayTideData ? $this->formatTideData($dayTideData) : null,
                'solunar_rating' => $solunarData['calculatedRating'] ?? null,
                'gregorian_month' => $day->format('F'),
                'gregorian_year' => (int)$day->format('Y'),
            ];
        }

        return [
            'year' => $selectedYear,
            'equinox_year' => $startEquinoxYear,
            'station_id' => $station->id,
            'station_name' => $station->name,
            'month_index' => (int)$monthIndex,
            'month_name' => $moon['name'],
            'start_date' => $monthStart->toDateString(),
            'end_date' => $monthStart->copy()->addDays(27)->toDateString(),
            'start_day_of_week' => $monthStart->format('l'),
            'days' => $monthDays,
            'is_leap_year' => $this->isLeapYear($selectedYear),
        ];
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

    public function getSolunarData($lat, $lng, $date, $offset = -4)
    {
        $cacheKey = "solunar_rating_{$lat}_{$lng}_{$date}";

        if ($cachedData = Cache::get($cacheKey)) {
            return $cachedData;
        }

        try {
            $formattedDate = Carbon::parse($date)->format('Ymd');
            $url = "https://api.solunar.org/solunar/{$lat},{$lng},{$formattedDate},{$offset}";

            $client = new Client(['timeout' => 10]);

            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);

            if (!$data || !isset($data['hourlyRating'])) {
                \Log::error("Solunar API returned invalid data for {$lat}, {$lng} on {$date}");
                return [
                    'hourlyRating' => [],
                    'calculatedRating' => 0,
                    'moonPhase' => null,
                ];
            }

            $hourly = $data['hourlyRating'];
            $totalHours = count($hourly);
            $totalRating = array_sum($hourly);
            $maxPossible = $totalHours * 100;
            $normalized = $maxPossible > 0 ? ($totalRating / $maxPossible) : 0;
            $starRating = round($normalized * 4, 1);

            $data['calculatedRating'] = $starRating;

            Cache::put($cacheKey, $data, now()->addDays(30));

            return $data;
        } catch (\Throwable $e) {
            \Log::error("Error fetching Solunar data: " . $e->getMessage());

            return [
                'hourlyRating' => [],
                'calculatedRating' => 0,
                'moonPhase' => null,
            ];
        }
    }

    private function fetchWeather($date)
    {
        try {
            $url = "https://api.open-meteo.com/v1/forecast?latitude={$this->selectedStation->latitude}&longitude={$this->selectedStation->longitude}&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weathercode&timezone=auto&start_date={$date}&end_date={$date}";

            $response = Http::timeout(10)->get($url);

            if ($response->failed()) {
                \Log::error("Failed to fetch weather data for {$date}. API response: " . $response->body());
                return null;
            }

            $data = $response->json()['daily'] ?? null;

            return $data ? [
                'max_temp' => $data['temperature_2m_max'][0] ?? null,
                'min_temp' => $data['temperature_2m_min'][0] ?? null,
                'precipitation' => $data['precipitation_sum'][0] ?? null,
                'weather_code' => $data['weathercode'][0] ?? null
            ] : null;
        } catch (\Throwable $e) {
            \Log::error("Error fetching weather data: " . $e->getMessage());
            return null;
        }
    }

    public function getTideData($date)
    {
        try {
            $startDate = Carbon::parse($date);
            $endDate = $startDate->copy();
            $products = ['predictions'];
            $noaaApiUrl = "https://api.tidesandcurrents.noaa.gov/api/prod/datagetter";

            foreach ($products as $product) {
                $response = Http::timeout(10)->get($noaaApiUrl, [
                    'begin_date' => $startDate->format('Ymd'),
                    'end_date' => $endDate->format('Ymd'),
                    'station' => $this->selectedStation->station_id ?? null,
                    'product' => $product,
                    'datum' => 'MLLW',
                    'time_zone' => 'gmt',
                    'units' => 'metric',
                    'format' => 'json'
                ]);

                if ($response->failed()) {
                    \Log::error("Failed to fetch tide data for product: $product on date: $date");
                    continue;
                }

                $data = $response->json();

                if (!isset($data['predictions']) && !isset($data['data'])) {
                    \Log::error("Invalid tide data format received for product: $product on date: $date");
                    continue;
                }

                match ($product) {
                    'predictions' => $this->storeTideData($data['predictions'], $this->stationId)
                };
            }
        } catch (\Throwable $e) {
            \Log::error("Error fetching tide data: " . $e->getMessage());
        }
    }

    private function storeTideData($predictions, $stationId)
    {
        $tideData = [];

        foreach ($predictions as $tide) {
            $date = Carbon::parse($tide['t'])->toDateString();
            $time = Carbon::parse($tide['t'])->format('H:i');
            $level = (float)$tide['v'];

            if (!isset($tideData[$date])) {
                $tideData[$date] = [
                    'station_id' => $stationId,
                    'year' => Carbon::parse($date)->year,
                    'month' => Carbon::parse($date)->format('F'),
                    'date' => $date,
                    'high_tide_time' => null,
                    'high_tide_level' => null,
                    'low_tide_time' => null,
                    'low_tide_level' => null,
                ];
            }

            if (is_null($tideData[$date]['high_tide_level']) || $level > $tideData[$date]['high_tide_level']) {
                $tideData[$date]['high_tide_time'] = $time;
                $tideData[$date]['high_tide_level'] = $level;
            }

            if (is_null($tideData[$date]['low_tide_level']) || $level < $tideData[$date]['low_tide_level']) {
                $tideData[$date]['low_tide_time'] = $time;
                $tideData[$date]['low_tide_level'] = $level;
            }
        }

        foreach ($tideData as $entry) {
            NOAATideForecast::updateOrCreate(
                ['station_id' => $stationId, 'date' => $entry['date']],
                $entry
            );
        }
    }

    private function getMoonData($date)
    {
        $date = Carbon::parse($date);
        $Y = $date->year;
        $M = $date->month;
        $D = $date->day;

        if ($M < 3) {
            $Y--;
            $M += 12;
        }

        $M++;
        $P2 = 2 * M_PI;

        $YY = $Y - intval((12 - $M) / 10);
        $MM = ($M + 9) % 12;

        $K1 = intval(365.25 * ($YY + 4712));
        $K2 = intval(30.6 * $MM + 0.5);
        $K3 = intval(intval($YY / 100 + 49) * 0.75) - 38;

        $J = $K1 + $K2 + $D + 59;
        if ($J > 2299160) {
            $J -= $K3;
        }

        $V = fmod(($J - 2451550.1) / 29.530588853, 1);
        if ($V < 0) $V += 1;
        $IP = $V;
        $AG = $IP * 29.53;
        $IP *= $P2;

        $V = fmod(($J - 2451562.2) / 27.55454988, 1);
        if ($V < 0) $V += 1;
        $DP = $V * $P2;

        $DI = 60.4 - 3.3 * cos($DP) - 0.6 * cos(2 * $IP - $DP) - 0.5 * cos(2 * $IP);

        $V = fmod(($J - 2451565.2) / 27.212220817, 1);
        if ($V < 0) $V += 1;
        $NP = $V * $P2;

        $LA = 5.1 * sin($NP);

        $V = fmod(($J - 2451555.8) / 27.321582241, 1);
        if ($V < 0) $V += 1;
        $RP = $V;

        $LO = 360 * $RP + 6.3 * sin($DP) + 1.3 * sin(2 * $IP - $DP) + 0.7 * sin(2 * $IP);

        $phases = [
            'New Moon 🌑',
            'Waxing Crescent 🌒',
            'First Quarter 🌓',
            'Waxing Gibbous 🌔',
            'Full Moon 🌕',
            'Waning Gibbous 🌖',
            'Last Quarter 🌗',
            'Waning Crescent 🌘',
        ];

        $phaseIndex = (int) floor($AG / 3.7);
        $phaseIndex = min($phaseIndex, 7);

        return [
            'age'   => intval($AG),
            'phase' => $phases[$phaseIndex],
            'DI'    => round($DI, 2),
            'LA'    => round($LA, 2),
            'LO'    => round($LO, 2),
        ];
    }
}
