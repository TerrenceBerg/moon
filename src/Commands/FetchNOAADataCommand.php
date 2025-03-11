<?php

namespace Tuna976\CustomCalendar\Commands;

use Carbon\CarbonTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Tuna976\CustomCalendar\Models\NOAATideForecast;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Carbon\Carbon;

class FetchNOAADataCommand extends Command
{
    protected $signature = 'noaa:fetch {days=7}';
    protected $description = 'Fetch and store NOAA data for all stations';

    public function handle()
    {
        ini_set('memory_limit', '-1');

        $stations = NOAAStation::all();

        if ($stations->isEmpty()) {
            $this->error("No NOAA stations found in the database.");
            return;
        }

        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays((int) $this->argument('days'));
        $products = ['predictions', 'water_temperature'];
        $noaaApiUrl = "https://api.tidesandcurrents.noaa.gov/api/prod/datagetter";

        foreach ($stations as $station) {
            $this->info("Fetching NOAA data for station: {$station->station_id} ({$station->name})");

            foreach ($products as $product) {
                $response = Http::get($noaaApiUrl, [
                    'begin_date' => $startDate->format('Ymd'),
                    'end_date' => $endDate->format('Ymd'),
                    'station' => $station->station_id,
                    'product' => $product,
                    'datum' => 'MLLW',
                    'time_zone' => 'gmt',
                    'units' => 'metric',
                    'format' => 'json'
                ]);

                if ($response->failed()) {
                    $this->error("Failed to fetch NOAA $product data for station {$station->station_id}.");
                    continue;
                }

                $data = $response->json();

                if (!isset($data['predictions']) && !isset($data['data'])) {
                    $this->error("Invalid response for NOAA $product for station {$station->station_id}.");
                    continue;
                }

                match ($product) {
                    'predictions' => $this->storeTideData($data['predictions'], $station->id),
                    'water_temperature' => $this->storeWaterTempData($data['data'], $station->id),
//                    'sunrise_sunset' => $this->storeSunriseSunsetData($data['data'], $station->id),
                };
            }
            $this->getSunriseSunset($station->id,$station->latitude,$station->longitude,$startDate,$endDate);
        }

        $this->info("NOAA data successfully fetched and stored for {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
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

    private function storeWaterTempData($data, $stationId)
    {
        foreach ($data as $record) {
            $date = Carbon::parse($record['t'])->toDateString();
            NOAATideForecast::updateOrCreate(
                ['station_id' => $stationId, 'date' => $date],
                ['water_temperature' => (float) $record['v']],
                ['year' => Carbon::parse($date)->year]
            );
        }
    }

    private function storeSunriseSunsetData($data, $stationId)
    {
        foreach ($data as $record) {
            $date = Carbon::parse($record['t'])->toDateString();
            NOAATideForecast::updateOrCreate(
                ['station_id' => $stationId, 'date' => $date],
                [
                    'sunrise' => Carbon::parse($record['sunrise'])->format('H:i'),
                    'sunset' => Carbon::parse($record['sunset'])->format('H:i')
                ]
            );
        }
    }

    public function getSunriseSunset($stationId, $latitude, $longitude, $startDate, $endDate)
    {
        try {
            $pstTimeZone = new CarbonTimeZone('America/Los_Angeles');
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            while ($start->lte($end)) {
                $response = Http::get("https://api.sunrise-sunset.org/json", [
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'formatted' => 0,
                    'date' => $start->format('Y-m-d')
                ]);

                $data = $response->json();

                if (!isset($data['results'])) {
                    $this->error("No sunrise/sunset data for {$start->format('Y-m-d')}");
                } else {
                    $sunrisePST = Carbon::parse($data['results']['sunrise'])->format('H:i');
                    $sunsetPST = Carbon::parse($data['results']['sunset'])->format('H:i');
                    dd($sunrisePST, $sunsetPST,$data,$stationId,$startDate,$endDate,$latitude,$longitude,$start);
                    NOAATideForecast::updateOrCreate(
                        ['station_id' => $stationId, 'date' => $start->toDateString()],
                        [
                            'sunrise' => $sunrisePST,
                            'sunset' => $sunsetPST,
                             'year' => Carbon::parse($start)->year,
                        ]
                    );

                    $this->info("Stored sunrise/sunset for {$start->format('Y-m-d')} at station {$stationId}");
                }

                $start->addDay();
            }
        } catch (\Exception $e) {
            $this->error("Error fetching sunrise/sunset data: " . $e->getMessage());
        }
    }
}
