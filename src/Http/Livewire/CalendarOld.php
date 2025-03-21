<?php

namespace Tuna976\CustomCalendar\Http\Livewire;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Tuna976\CustomCalendar\CustomCalendar;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Tuna976\CustomCalendar\Models\NOAATideForecast;

class CalendarOld extends Component
{
    protected $listeners = ['updateStation' => 'setStation'];

    public $stations, $selectedStationId, $selectedStation, $location, $calendarData;
    public $loading = false, $selectedDate, $modalData, $showModal = false;
    public $temperatureUnit = 'C';
    public $stationProducts;


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

        $station = NoaaStation::with('currentStation')->where('station_id', $this->selectedStationId)->first();
        $this->currentsData = null;

        if ($station && $station->currentStation) {
            $this->currentsData = $this->fetchCurrentsData($station->currentStation->station_id, $date);
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
        $products = [
            'predictions',      // Ocean Tides
            'currents',         // Ocean Currents
            'salinity',         // Salinity
            'water_temperature', // Water Temperature
            'wave_direction',   // Wave Direction
            'wave_height',      // Wave Height
            'wave_period'       // Wave Period
        ];
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


            foreach ($data['predictions'] ?? $data['data'] as $record) {
                $date = Carbon::parse($record['t'])->toDateString();
                $time = Carbon::parse($record['t'])->format('H:i');
                $value = (float)$record['v'];

                if (!isset($tideData[$date])) {
                    $tideData[$date] = [
                        'station_id' => $this->selectedStationId,
                        'year' => Carbon::parse($date)->year,
                        'month' => Carbon::parse($date)->format('F'),
                        'date' => $date,
                        'high_tide_time' => null,
                        'high_tide_level' => null,
                        'low_tide_time' => null,
                        'low_tide_level' => null,
                        'ocean_current' => null,
                        'salinity' => null,
                        'water_temperature' => null,
                        'wave_direction' => null,
                        'wave_height' => null,
                        'wave_period' => null,
                    ];
                }

                match ($product) {
                    'predictions' =>$tideData[$date]['predictions']= $value,
                    'currents' => $tideData[$date]['ocean_current'] = $value,
                    'salinity' => $tideData[$date]['salinity'] = $value,
                    'water_temperature' => $tideData[$date]['water_temperature'] = $value,
                    'wave_direction' => $tideData[$date]['wave_direction'] = $value,
                    'wave_height' => $tideData[$date]['wave_height'] = $value,
                    'wave_period' => $tideData[$date]['wave_period'] = $value,
                };
            }
            dd($tideData);
        }

    }
    private function storeTideData($predictions, $stationId)
    {
        $tideData = [];

        foreach ($predictions as $tide) {
            $date = Carbon::parse($tide['t'])->toDateString();
            $time = Carbon::parse($tide['t'])->format('H:i');
            $level = (float) $tide['v'];

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

    public function loadStationProducts()
    {
        if (!$this->selectedStation || empty($this->selectedStation->products)) {
            return;
        }

        $productUrl = json_decode($this->selectedStation->products, true)['self'];

        $response = Http::get($productUrl);

        if ($response->failed()) {
            return;
        }

        $data = $response->json();

        if (!isset($data['products'])) {
            return;
        }

        $this->stationProducts = collect($data['products'])->pluck('name')->toArray();
    }
    public function fetchNoaaData()
    {
        $productData = [];

        // Decode the JSON column to get the products API URL
        $productsInfo = json_decode($this->selectedStation->products, true);
        $productsUrl = $productsInfo['self'] ?? null;

        if (!$productsUrl) {
            return;
        }

        // Step 1: Fetch available products from NOAA API
        $response = Http::get($productsUrl);

        if ($response->failed()) {
            return;
        }

        $productsList = $response->json()['products'] ?? [];

        // Step 2: Iterate through each product and fetch relevant data
//        foreach ($productsList as $product) {
//            $productName = $product['name'];
//            $productKey = strtolower(str_replace(' ', '_', $productName)); // Normalize key

            $noaaApiUrl = 'https://api.tidesandcurrents.noaa.gov/api/prod/datagetter';

            // Step 3: Fetch data for each product
            $response = Http::get($noaaApiUrl, [
                'begin_date' => now()->subDays(7)->format('Ymd'), // Example: Last 7 days
                'end_date' => now()->format('Ymd'),
                'station' => $this->selectedStation->station_id,
                'product' => 'currents',
                'datum' => 'MLLW',
                'time_zone' => $this->selectedStation->timezone,
                'units' => 'metric',
                'format' => 'json'
            ]);
            dd($response->json(),$this->selectedStation->station_id);

            if ($response->successful()) {
                $data = $response->json();
//                $productData[$productName] = $data['predictions'] ?? $data['data'] ?? [];
            } else {
//                $productData[$productName] = 'Error fetching data';
            }
//        }
        dd($data);

        $noaaApiUrl = "https://api.tidesandcurrents.noaa.gov/api/prod/datagetter";

        // Fetch the products JSON from the station table
        $productsJson = json_decode($this->selectedStation->products, true);
        $productsUrl = $productsJson['self'] ?? null;

        if (!$productsUrl) {
            return; // Exit if no products URL is found
        }

        // Fetch the available products for the selected station
        $response = Http::get($productsUrl);

        if (!$response->successful()) {
            return; // Exit if the API request fails
        }

        $availableProducts = $response->json();
        $productList = collect($availableProducts['products'] ?? [])->pluck('value')->toArray();

        $tideData = [];
        dd($productList);

        foreach ($productList as $product) {
            $response = Http::get($noaaApiUrl, [
                'begin_date' => now()->subDays(7)->format('Ymd'), // Fetch last 7 days of data
                'end_date' => now()->format('Ymd'),
                'station' => $this->selectedStation->station_id,
                'product' => $product,
                'datum' => 'MLLW',
                'time_zone' => 'gmt',
                'units' => 'metric',
                'format' => 'json'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $tideData[$product] = $data['predictions'] ?? $data['data'] ?? [];
            }
        }

        dd($tideData); // Store data in a Livewire property
    }
    public function fetchCurrentsData($stationId, $date)
    {
        $response = Http::get('https://api.tidesandcurrents.noaa.gov/api/prod/datagetter', [
            'station' => $stationId,
            'product' => 'currents_predictions',
            'date' => Carbon::parse($date)->format('Ymd'),
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
}
