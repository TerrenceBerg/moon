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
        ini_set('max_execution_time', 100000);
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
            $cacheKeys = [];
            $dateMap = [];

            foreach ($moons as $moon) {
                $monthStart = $vernalEquinox->copy()->addDays($moon['offset']);
                for ($i = 0; $i < 28; $i++) {
                    $date = $monthStart->copy()->addDays($i)->toDateString();
                    $cacheKey = "solunar_rating_{$station->latitude}_{$station->longitude}_{$date}";
                    $cacheKeys[] = $cacheKey;
                    $dateMap[$cacheKey] = $date;
                }
            }

            $solunarBulk = Cache::many($cacheKeys);

            foreach ($moons as $moon) {
                $monthStart = $vernalEquinox->copy()->addDays($moon['offset']);
                $monthDays = [];

                for ($i = 0; $i < 28; $i++) {
                    $dayDate = $monthStart->copy()->addDays($i)->toDateString();
                    $dayTideData = $noaaData[$dayDate][0] ?? null;

                    $cacheKey = "solunar_rating_{$station->latitude}_{$station->longitude}_{$dayDate}";
                    $solunarData = $solunarBulk[$cacheKey] ?? null;

                    // fallback to live fetch if not in cache
                    if (!$solunarData) {
                        $solunarData = $this->getSolunarData($station->latitude, $station->longitude, $dayDate);
                    }
                    $solunarRating = $solunarData['calculatedRating'] ?? null;
//                    $moonIcons = [
//                        'New Moon' => '🌑',
//                        'Waxing Crescent' => '🌒',
//                        'First Quarter' => '🌓',
//                        'Waxing Gibbous' => '🌔',
//                        'Full Moon' => '🌕',
//                        'Waning Gibbous' => '🌖',
//                        'Last Quarter' => '🌗',
//                        'Waning Crescent' => '🌘',
//                    ];
//
//                    $moonPhaseKey = $solunarData['moonPhase'] ?? null;
//
//                    $moonIcon = $moonIcons[$moonPhaseKey] ?? null;
                    $monthDays[] = [
                        'date' => $dayDate,
                        'day_of_week' => Carbon::parse($dayDate)->format('l'),
                        'julian_day' => Carbon::parse($dayDate)->dayOfYear,
                        'gregorian_date' => Carbon::parse($dayDate)->format('M j, Y'),
//                        'moon_phase' => $moonIcon." ".$moonPhaseKey,
                        'moon_phase' => $this->getMoonPhase($dayDate),
                        'is_today' => Carbon::parse($dayDate)->isToday(),
                        'tide_data' => $dayTideData ? $this->formatTideData($dayTideData) : null,
                        'solunar_rating' => $solunarRating,
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
//                'solar_events' => $this->formatSolarEvents($solarEvents[$year]),
                'is_leap_year' => $this->isLeapYear($year),
                'months' => $months,
            ];
        }

        return $calendarData;
    }

    public function generateDayData($date = null, $stationId = null)
    {
        $date = Carbon::parse($date ?? now())->toDateString();
        $station = NOAAStation::where('id', $stationId ?? $this->stationId)->firstOrFail();

        // Solar Event
        $solarEvent = SolarEvent::where('year', Carbon::parse($date)->year)->first();
        $vernalEquinox = $solarEvent ? Carbon::parse($solarEvent->march_equinox) : null;

        // Moon Phase
        $moonPhase = $this->getMoonPhase($date);

        // Tide Data
        $tideData = NOAATideForecast::where('station_id', $station->id)
            ->where('date', $date)
            ->first();

        if (!$tideData || !$tideData->high_tide_time || !$tideData->low_tide_time) {
            $this->selectedStation = $station;
            $this->selectedStationId = $station->id;
            $this->getTideData($date);
            $tideData = NOAATideForecast::where('station_id', $station->id)
                ->where('date', $date)
                ->first();
        }

        // Weather Data
        if ($tideData && (!$tideData->min_temp || !$tideData->precipitation || !$tideData->weather_code)) {
            $weatherData = $this->fetchWeather($date);
            if ($weatherData) {
                $tideData->update($weatherData);
            }
        }

        // Solunar Rating
        $cacheKey = "solunar_rating_{$station->latitude}_{$station->longitude}_{$date}";
        $solunarData = Cache::get($cacheKey);
        if (!$solunarData) {
            $solunarData = $this->getSolunarData($station->latitude, $station->longitude, $date);
            if ($solunarData) {
                Cache::put($cacheKey, $solunarData, now()->addDays(2));
            }
        }

        return [
            'date' => $date,
            'gregorian_date' => Carbon::parse($date)->format('l, M j, Y'),
            'julian_day' => Carbon::parse($date)->dayOfYear,
            'is_today' => Carbon::parse($date)->isToday(),
            'moon_phase' => $moonPhase,
            'all_data' => $tideData->toArray(),
            'solunar_rating' => $solunarData['calculatedRating'] ?? null,
            'solunar_data' => $solunarData ?? null,
            'vernal_equinox' => $vernalEquinox ? $vernalEquinox->format('Y-m-d H:i:s') : null,
        ];
    }

    public function generateDayDataLive($lat, $lon, $date = null)
    {
        $date = Carbon::parse($date ?? now())->toDateString();

        $moonPhase = $this->getMoonPhase($date);

        $tideFetcher = new TideDataFetcher();
        $tideData = $tideFetcher->fetchTideData($lat, $lon, $date);

        $solunarData = $this->getSolunarData($lat, $lon, $date);
        return [
            'date' => $date,
            'gregorian_date' => Carbon::parse($date)->format('l, M j, Y'),
            'julian_day' => Carbon::parse($date)->dayOfYear,
            'is_today' => Carbon::parse($date)->isToday(),
            'moon_phase' => $moonPhase,
            'all_data' => $tideData,
            'solunar_rating' => $solunarData['calculatedRating'] ?? null,
            'solunar_data' => $solunarData ?? null,
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

    public function getSolunarData($lat, $lng, $date, $offset = -4)
    {
        $cacheKey = "solunar_rating_{$lat}_{$lng}_{$date}";
        // Check cache
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        try {
            $formattedDate = Carbon::parse($date)->format('Ymd');
            $url = "https://api.solunar.org/solunar/{$lat},{$lng},{$formattedDate},{$offset}";
            $client = new Client();
            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);
            if (!$data || !isset($data['hourlyRating'])) {
                return null;
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

        } catch (\Exception $e) {
            return null;
        }
    }

    private function fetchWeather($date)
    {
        $url = "https://api.open-meteo.com/v1/forecast?latitude={$this->selectedStation->latitude}&longitude={$this->selectedStation->longitude}&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weathercode&timezone=auto&start_date={$date}&end_date={$date}";
        $data = json_decode(file_get_contents($url), true)['daily'] ?? null;
        return $data ? [
            'max_temp' => $data['temperature_2m_max'][0] ?? null,
            'min_temp' => $data['temperature_2m_min'][0] ?? null,
            'precipitation' => $data['precipitation_sum'][0] ?? null,
            'weather_code' => $data['weathercode'][0] ?? null
        ] : null;
    }
    public function getTideData($date)
    {
        $startDate = Carbon::parse($date);
        $endDate = $startDate->copy();
        $products = ['predictions'];
        $noaaApiUrl = "https://api.tidesandcurrents.noaa.gov/api/prod/datagetter";
        foreach ($products as $product) {
            $response = Http::get($noaaApiUrl, [
                'begin_date' => $startDate->format('Ymd'),
                'end_date' => $endDate->format('Ymd'),
                'station' => $this->selectedStation->station_id,
                'product' => $product,
                'datum' => 'MLLW',
                'time_zone' => 'gmt',
                'units' => 'metric',
                'format' => 'json'
            ]);

            if ($response->failed()) {
                continue;
            }

            $data = $response->json();

            if (!isset($data['predictions']) && !isset($data['data'])) {
                continue;
            }

            match ($product) {
                'predictions' => $this->storeTideData($data['predictions'], $this->selectedStationId)
            };
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

}
