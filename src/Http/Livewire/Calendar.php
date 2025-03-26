<?php

namespace Tuna976\CustomCalendar\Http\Livewire;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Tuna976\CustomCalendar\CustomCalendar;
use Tuna976\CustomCalendar\Models\NOAAEnvironmentalReading;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Tuna976\CustomCalendar\Models\NOAATideForecast;

class Calendar extends Component
{
    protected $listeners = ['updateStation' => 'setStation'];

    public $stations, $selectedStationId, $selectedStation, $location, $calendarData;
    public $loading = false, $selectedDate, $modalData, $showModal = false;
    public $temperatureUnit = 'C';
    public $currentsData,$stationMoreData,$solunarData;


    public function mount()
    {
        $this->location = $this->getUserLocation();
        if (!$this->location) return response()->json(['error' => 'Unable to determine location.'], 400);

        $nearestStation = NOAAStation::getNearestStation($this->location['lat'], $this->location['lon']);
        if (!$nearestStation) return response()->json(['error' => 'No station found.'], 404);

        $this->stations = NOAAStation::orderBy('name')->get();
        $this->selectedStationId = $nearestStation->id ?? $this->stations->first()->id;
        $this->selectedStation = NOAAStation::find($this->selectedStationId);
        $this->loadCalendar();
    }

    public function loadCalendar()
    {
        $this->loading = true;
        $this->calendarData = (new CustomCalendar(now()->year, $this->selectedStationId))->generateCalendar();
        $this->loading = false;
    }

    public function setStation($stationId)
    {
        $this->selectedStationId = $stationId;
        $this->selectedStation = NOAAStation::find($stationId);
        $this->loadCalendar();
    }

    public function loadMoreData($date)
    {
        $this->selectedDate = $date;
        $this->modalData = NOAATideForecast::where('station_id', $this->selectedStationId)
            ->whereDate('date', $date)
            ->first();

        if ($this->modalData) {
            if (!isset($this->modalData->sunrise, $this->modalData->sunset)) {
                $this->storeSunriseSunsetData($date);
                $this->modalData = NOAATideForecast::where('station_id', $this->selectedStationId)
                    ->whereDate('date', $date)
                    ->first();
            }

            $this->modalData->moon_phase = $this->getMoonPhase($date);

            if (!$this->modalData->min_temp || !$this->modalData->precipitation || !$this->modalData->weather_code) {
                $weatherData = $this->fetchWeather($date);
                if ($weatherData) {
                    $this->modalData->update($weatherData);
                }
            }
            if (!$this->modalData->low_tide_time || !$this->modalData->low_tide_level || !$this->modalData->high_tide_level || !$this->modalData->high_tide_time) {
                $this->getTideData($date);
            }
        } else {
            $this->getTideData($date);
            $this->modalData = NOAATideForecast::where('station_id', $this->selectedStationId)
                ->whereDate('date', $date)
                ->first();
            $weatherData = $this->fetchWeather($date);
            if ($weatherData) {
                $this->modalData->update($weatherData);
            }
        }
//        $this->currentsData = null;
//        $this->fetchAllNoaaProducts($this->selectedStation->station_id, $date);
//        if ($this->selectedStation && $this->selectedStation->currentStation) {
////            $this->getStationData($this->selectedStation->station_id, $date);
//
//            $isToday = Carbon::parse($date)->isToday();
//            if ($isToday) {
//                $currents = $this->fetchCurrentsData($this->selectedStation->currentStation->station_id, 'today');
//                if (!empty($currents['current_predictions']['cp'])) {
//                    $this->currentsData = collect($currents['current_predictions']['cp'])->take(6);
//                }
//            }
//        }

        if ($this->selectedStation && $this->selectedStation->latitude && $this->selectedStation->longitude) {
            $lat = $this->selectedStation->latitude;
            $lng = $this->selectedStation->longitude;
            $cacheKey = "solunar_rating_{$lat}_{$lng}_{$date}";

            $solunarData = Cache::get($cacheKey);

            if (!$solunarData) {
                $solunarData = $this->getSolunarData($lat, $lng, $date);
                if ($solunarData) {
                    Cache::put($cacheKey, $solunarData, now()->addDays(2));
                }
            }
            $this->solunarData = $solunarData;
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedDate = $this->modalData = null;
        $this->dispatch('preserve-scroll');

    }

    public function render()
    {
        return view('customcalendar::livewire.calendar', ['calendarData' => $this->calendarData]);
    }

    private function getMoonPhase($date)
    {
        $moonPhases = ['New Moon 🌑', 'Waxing Crescent 🌒', 'First Quarter 🌓', 'Waxing Gibbous 🌔', 'Full Moon 🌕', 'Waning Gibbous 🌖', 'Last Quarter 🌗', 'Waning Crescent 🌘'];
        $daysSinceNewMoon = Carbon::create(2000, 1, 6, 18, 14, 0)->floatDiffInDays(Carbon::parse($date));
        return $moonPhases[(int)round(($daysSinceNewMoon % 29.53058867) / 29.53058867 * 8) % 8] ?? null;
    }

    private function storeSunriseSunsetData($date)
    {
        try {
            $station = $this->selectedStation;
            $response = Http::get('https://api.sunrise-sunset.org/json', [
                'lat' => $station->latitude, 'lng' => $station->longitude, 'formatted' => 0, 'date' => $date
            ]);

            if ($response->failed()) return;

            $data = $response->json()['results'];
            $tz = new CarbonTimeZone('America/Los_Angeles');

            NOAATideForecast::updateOrCreate(
                ['station_id' => $station->id, 'date' => $date],
                ['sunrise' => Carbon::parse($data['sunrise'])->setTimezone($tz)->format('H:i'),
                    'sunset' => Carbon::parse($data['sunset'])->setTimezone($tz)->format('H:i')]
            );
        } catch (\Exception $e) {
            \Log::error("Sunrise/Sunset data fetch failed: " . $e->getMessage());
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

    private function getUserLocation()
    {
        $ip = request()->ip();
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return ['lat' => 34.0522, 'lon' => -118.2437, 'city' => 'Los Angeles'];
        }

        try {
            $response = Http::timeout(5)->get("https://ipapi.co/{$ip}/json");
            if ($response->failed()) {
                return null;
            }
            $data = $response->json();
            return [
                'lat' => $data['latitude'] ?? 0,
                'lon' => $data['longitude'] ?? 0,
                'city' => $data['city'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            \Log::error("Failed to fetch user location: " . $e->getMessage());
            return null;
        }
    }

    public function getTemperature($temp)
    {
        if ($this->temperatureUnit === 'F') {
            return round(($temp * 9 / 5) + 32, 1) . '°F';
        }
        return round($temp, 1) . '°C';
    }

    public function toggleTemperatureUnit()
    {
        $this->temperatureUnit = $this->temperatureUnit === 'C' ? 'F' : 'C';
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

    public function fetchCurrentsData($stationId, $date)
    {

        $response = Http::get('https://api.tidesandcurrents.noaa.gov/api/prod/datagetter', [
            'station' => $stationId,
            'product' => 'currents_predictions',
            'date' => $date,
            'datum' => 'MLLW',
            'units' => 'metric',
            'time_zone' => 'gmt',
            'interval' => 'h',
            'format' => 'json',
        ]);


        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
//    public function fetchAllNoaaProducts($stationId, $date)
//    {
//        $results = [];
//
//        $startDate = Carbon::parse($date)->format('Ymd');
//        $endDate = Carbon::parse($date)->format('Ymd');
//
//        $productsWithDatum = ['predictions', 'water_level'];
//        $productsStandard = [
//            'wind',
//            'air_temperature',
//            'water_temperature',
//            'air_pressure',
//            'humidity',
//            'salinity',
//            'visibility',
//            'dew_point'
//        ];
//        foreach ($productsWithDatum as $product) {
//            $queryParams = [
//                'station' => $stationId,
//                'product' => $product,
//                'begin_date' => $startDate,
//                'end_date' => $endDate,
//                'datum' => 'MLLW',
//                'units' => 'metric',
//                'time_zone' => 'gmt',
//                'format' => 'json',
//            ];
//
//            $response = Http::get('https://api.tidesandcurrents.noaa.gov/api/prod/datagetter', $queryParams);
//
//            $results[$product] = $response->successful()
//                ? ($response->json() ?? 'No data returned')
//                : 'Request failed (' . $response->status() . ')';
//        }
//
//        foreach ($productsStandard as $product) {
//            $queryParams = [
//                'station' => $stationId,
//                'product' => $product,
//                'begin_date' => $startDate,
//                'end_date' => $endDate,
//                'units' => 'metric',
//                'time_zone' => 'gmt',
//                'format' => 'json',
//            ];
//
//            $response = Http::get('https://api.tidesandcurrents.noaa.gov/api/prod/datagetter', $queryParams);
//
//            $results[$product] = $response->successful()
//                ? ($response->json() ?? 'No data returned')
//                : 'Request failed (' . $response->status() . ')';
//        }
////        dd($results);
//        $this->stationMoreData =$results;
//    }
    public function fetchAllNoaaProducts($stationId, $date)
    {
        $results = [];

        $startDate = Carbon::parse($date)->format('Ymd');
        $endDate = Carbon::parse($date)->format('Ymd');

        $productsWithDatum = ['predictions', 'water_level'];
        $productsStandard = [
            'wind', 'air_temperature', 'water_temperature',
            'air_pressure', 'humidity', 'salinity',
            'visibility', 'dew_point'
        ];

        $allProducts = array_merge($productsWithDatum, $productsStandard);

        foreach ($allProducts as $product) {
            $queryParams = [
                'station' => $stationId,
                'product' => $product,
                'begin_date' => $startDate,
                'end_date' => $endDate,
                'units' => 'metric',
                'time_zone' => 'gmt',
                'format' => 'json',
            ];

            if (in_array($product, $productsWithDatum)) {
                $queryParams['datum'] = 'MLLW';
            }

            $response = Http::get('https://api.tidesandcurrents.noaa.gov/api/prod/datagetter', $queryParams);

            if ($response->successful() && isset($response['data'])) {
                $results[$product] = $response['data'];

                foreach ($response['data'] as $item) {
                    if (!isset($item['t'], $item['v'])) continue;

                    $timestamp = Carbon::parse($item['t']);

                    NOAAEnvironmentalReading::updateOrCreate([
                        'station_id'   => $stationId,
                        'product'      => $product,
                        'reading_time' => $timestamp,
                    ], [
                        'date'  => $timestamp->toDateString(),
                        'value' => $item['v'],
                    ]);
                }
            } else {
                $results[$product] = 'Request failed (' . $response->status() . ')';
            }
        }
        $this->stationMoreData = $results;
    }

    public function getMonthlySolunarRatings($latitude, $longitude, $month, $year, $offset = -4)
    {
        $cacheKey = "solunar_{$latitude}_{$longitude}_{$year}_{$month}_offset{$offset}";

        return Cache::remember($cacheKey, now()->addDays(1), function () use ($latitude, $longitude, $month, $year, $offset) {
            $client = new Client();
            $start = Carbon::createFromDate($year, $month, 1);
            $end = $start->copy()->endOfMonth();

            $ratings = [];

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $formattedDate = $date->format('Ymd');
                $url = "https://api.solunar.org/solunar/{$latitude},{$longitude},{$formattedDate},{$offset}";

                try {
                    $response = $client->get($url);
                    $data = json_decode($response->getBody(), true);

                    $ratings[$date->format('Y-m-d')] = $data['rating'] ?? null;
                } catch (\Exception $e) {
                    $ratings[$date->format('Y-m-d')] = null;
                }
            }

            return $ratings;
        });
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


}
