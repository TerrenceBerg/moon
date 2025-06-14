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
    protected $listeners = ['updateLocationFromBrowser' => 'setLocationFromBrowser', 'updateStation' => 'setStation'];

    public $stations, $selectedStationId, $selectedStation, $location, $calendarData;
    public $loading = false, $selectedDate, $modalData, $showModal = false;
    public $temperatureUnit = 'F';
    public $currentsData, $stationMoreData, $solunarData;


    public function mount()
    {

        $this->temperatureUnit = config('temperature_unit', 'F');

//        $this->location = $this->getUserLocation();
//        if (!$this->location) return response()->json(['error' => 'Unable to determine location.'], 400);

        $nearestStation = NOAAStation::find(218);
//        if (!$nearestStation) return response()->json(['error' => 'No station found.'], 404);

        $this->stations = NOAAStation::orderBy('name')->get();
//        dd($this->stations);
        $this->selectedStationId = $nearestStation->id;
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
            if (!$this->modalData->min_temp || !$this->modalData->precipitation || !$this->modalData->weather_code) {
                $weatherData = $this->fetchWeather($date);
                if ($weatherData) {
                    $this->modalData->update($weatherData);
                }
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
        $date = Carbon::parse($date);
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        if ($month < 3) {
            $year--;
            $month += 12;
        }

        ++$month;

        $c = 365.25 * $year;
        $e = 30.6 * $month;
        $jd = $c + $e + $day - 694039.09;  // jd is total days elapsed
        $jd /= 29.5305882;                 // divide by the moon cycle
        $b = (int)$jd;                     // int(jd) -> completed cycles
        $jd -= $b;                         // subtract integer part to leave fractional part
        $phaseIndex = (int)round($jd * 8) % 8;

        $phases = [
            'New Moon 🌑',
            'Waxing Crescent 🌒',
            'First Quarter 🌓',
            'Waxing Gibbous 🌔',
            'Full Moon 🌕',
            'Waning Gibbous 🌖',
            'Last Quarter 🌗',
            'Waning Crescent 🌘'
        ];

        return $phases[$phaseIndex];
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

        $M++; // Adjust for Julian algorithm
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

        // Synodic (illumination) phase
        $V = fmod(($J - 2451550.1) / 29.530588853, 1);
        if ($V < 0) $V += 1;
        $IP = $V;
        $AG = $IP * 29.53; // Moon's age in days
        $IP *= $P2;

        // Distance (anomalistic phase)
        $V = fmod(($J - 2451562.2) / 27.55454988, 1);
        if ($V < 0) $V += 1;
        $DP = $V * $P2;

        $DI = 60.4 - 3.3 * cos($DP) - 0.6 * cos(2 * $IP - $DP) - 0.5 * cos(2 * $IP);

        // Latitude (draconic phase)
        $V = fmod(($J - 2451565.2) / 27.212220817, 1);
        if ($V < 0) $V += 1;
        $NP = $V * $P2;

        $LA = 5.1 * sin($NP);

        // Longitude (sidereal phase)
        $V = fmod(($J - 2451555.8) / 27.321582241, 1);
        if ($V < 0) $V += 1;
        $RP = $V;

        $LO = 360 * $RP + 6.3 * sin($DP) + 1.3 * sin(2 * $IP - $DP) + 0.7 * sin(2 * $IP);

        // Determine moon phase
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
        try {
            $url = "https://api.open-meteo.com/v1/forecast?latitude={$this->selectedStation->latitude}&longitude={$this->selectedStation->longitude}&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weathercode&timezone=auto&start_date={$date}&end_date={$date}";
            $response = file_get_contents($url);

            if ($response === false) {
                throw new \Exception("Failed to fetch weather data from Open Meteo API.");
            }

            $data = json_decode($response, true)['daily'] ?? null;

            if (!$data) {
                throw new \Exception("No weather data found for the given date.");
            }

            return [
                'max_temp' => $data['temperature_2m_max'][0] ?? null,
                'min_temp' => $data['temperature_2m_min'][0] ?? null,
                'precipitation' => $data['precipitation_sum'][0] ?? null,
                'weather_code' => $data['weathercode'][0] ?? null,
            ];

        } catch (\Exception $e) {
            \Log::error("Weather fetch error: " . $e->getMessage());
            return null;
        }
    }

    private function getUserLocation()
    {
//        $ip = request()->ip();
//        if (in_array($ip, ['127.0.0.1', '::1'])) {
        return ['lat' => 34.0522, 'lon' => -118.2437, 'city' => 'Los Angeles'];
//            return ['lat' => 36.7783, 'lon' => 119.4179, 'city' => 'Los Angeles'];
//            return ['lat' => 32.715736, 'lon' => -117.161087, 'city' => 'San Diego'];
//        }

//        try {
//            $response = Http::timeout(5)->get("https://ipapi.co/{$ip}/json");
//            if ($response->failed()) {
//                return null;
//            }
//            $data = $response->json();
//            return [
//                'lat' => $data['latitude'] ?? 0,
//                'lon' => $data['longitude'] ?? 0,
//                'city' => $data['city'] ?? 'Unknown'
//            ];
//        } catch (\Exception $e) {
//            \Log::error("Failed to fetch user location: " . $e->getMessage());
//            return null;
//        }
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
        try {
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
        } catch (\Exception $e) {
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

    public function fetchCurrentsData($stationId, $date)
    {
        try {
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

        } catch (\Exception $e) {
            \Log::error("Currents data fetch error: " . $e->getMessage());
            return null;
        }
    }

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
                        'station_id' => $stationId,
                        'product' => $product,
                        'reading_time' => $timestamp,
                    ], [
                        'date' => $timestamp->toDateString(),
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

    public function setLocationFromBrowser($lat, $lon, $city)
    {
        $this->location = [
            'lat' => $lat,
            'lon' => $lon,
            'city' => $city,
        ];
        if (!$this->location) return response()->json(['error' => 'Unable to determine location.'], 400);

        $nearestStation = NOAAStation::getNearestStation($this->location['lat'], $this->location['lon']);
        if (!$nearestStation) return response()->json(['error' => 'No station found.'], 404);

        $this->stations = NOAAStation::orderBy('name')->get();
        $this->selectedStationId = $nearestStation->id ?? $this->stations->first()->id;
        $this->selectedStation = NOAAStation::find($this->selectedStationId);
        $this->loadCalendar();
    }


}
