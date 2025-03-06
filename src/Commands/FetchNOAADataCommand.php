<?php

namespace Tuna976\CustomCalendar\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Tuna976\CustomCalendar\Models\NOAATideForecast;
use Carbon\Carbon;

class FetchNOAADataCommand extends Command
{
    protected $signature = 'noaa:fetch {days=7}';
    protected $description = 'Fetch and store NOAA data';

    public function handle()
    {
        ini_set('memory_limit', '-1');
        $noaaStation = "9414523";
        $noaaApiUrl = "https://api.tidesandcurrents.noaa.gov/api/prod/datagetter";

        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays((int) $this->argument('days'));

        $products = ['predictions', 'water_temperature', 'sunrise_sunset'];

        foreach ($products as $product) {
            $response = Http::get($noaaApiUrl, [
                'begin_date' => $startDate->format('Ymd'),
                'end_date' => $endDate->format('Ymd'),
                'station' => $noaaStation,
                'product' => $product,
                'datum' => 'MLLW',
                'time_zone' => 'gmt',
                'units' => 'metric',
                'format' => 'json'
            ]);

            if ($response->failed()) {
                $this->error("Failed to fetch NOAA $product data.");
                continue;
            }

            $data = $response->json();

            if (!isset($data['predictions']) && !isset($data['data'])) {
                $this->error("Invalid response for NOAA $product.");
                continue;
            }

            match ($product) {
                'predictions' => $this->storeTideData($data['predictions']),
                'water_temperature' => $this->storeWaterTempData($data['data']),
                'wind' => $this->storeWindData($data['data']),
                'sunrise_sunset' => $this->storeSunriseSunsetData($data['data']),
            };
        }

        $this->info("NOAA data successfully fetched and stored for {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
    }

    private function storeTideData($predictions)
    {
        $tideData = [];

        foreach ($predictions as $tide) {
            $date = Carbon::parse($tide['t'])->toDateString();
            $time = Carbon::parse($tide['t'])->format('H:i');
            $level = (float) $tide['v'];

            if (!isset($tideData[$date])) {
                $tideData[$date] = [
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
            NOAATideForecast::updateOrCreate(['date' => $entry['date']], $entry);
        }
    }

    private function storeWaterTempData($data)
    {
        foreach ($data as $record) {
            $date = Carbon::parse($record['t'])->toDateString();
            NOAATideForecast::updateOrCreate(
                ['date' => $date],
                ['water_temperature' => (float) $record['v']]
            );
        }
    }

    private function storeWindData($data)
    {
        foreach ($data as $record) {
            if (!isset($record['t'], $record['s'], $record['d'], $record['dr'], $record['g'])) {
                continue;
            }

            $date = Carbon::parse($record['t'])->toDateString();
            $windSpeed = is_numeric($record['s']) ? (float) $record['s'] : null;
            $windDirection = $record['dr'];
            $windGust = is_numeric($record['g']) ? (float) $record['g'] : null;
            $windAngle = is_numeric($record['d']) ? (int) $record['d'] : null;

            NOAATideForecast::updateOrCreate(
                ['date' => $date],
                [
                    'wind_speed' => $windSpeed,
                    'wind_direction' => $windDirection,
                    'wind_gust' => $windGust,
                    'wind_angle' => $windAngle
                ]
            );
        }
    }

    private function storeSunriseSunsetData($data)
    {
        foreach ($data as $record) {
            $date = Carbon::parse($record['t'])->toDateString();
            NOAATideForecast::updateOrCreate(
                ['date' => $date],
                [
                    'sunrise' => Carbon::parse($record['sunrise'])->format('H:i'),
                    'sunset' => Carbon::parse($record['sunset'])->format('H:i')
                ]
            );
        }
    }
}
