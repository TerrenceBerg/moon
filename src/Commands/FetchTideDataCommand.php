<?php

namespace Tuna976\CustomCalendar\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Tuna976\CustomCalendar\Models\TideData;
use Carbon\Carbon;

class FetchTideDataCommand extends Command
{
    protected $signature = 'tides:fetch';
    protected $description = 'Fetch and store NOAA tide data for the next 7 days';

    public function handle()
    {
        ini_set('memory_limit', '512M');
        $noaaStation = "9410170"; // San Diego, CA NOAA Tide Station ID
        $noaaApiUrl = "https://api.tidesandcurrents.noaa.gov/api/prod/datagetter";

        $startDate = Carbon::today()->subYears(1);
        $endDate = $startDate->copy()->addYears(2);

        $response = Http::get($noaaApiUrl, [
            'begin_date' => $startDate->format('Ymd'),
            'end_date' => $endDate->format('Ymd'),
            'station' => $noaaStation,
            'product' => 'predictions',
            'datum' => 'MLLW',
            'time_zone' => 'gmt',
            'units' => 'metric',
            'format' => 'json'
        ]);

        if ($response->failed()) {
            $this->error("Failed to fetch NOAA tide data.");
            return;
        }

        $data = $response->json();

        if (!isset($data['predictions'])) {
            $this->error("Invalid response structure from NOAA API.");
            return;
        }

        $tideDataByDate = [];

        foreach ($data['predictions'] as $tide) {
            $date = Carbon::parse($tide['t'])->toDateString();
            $time = Carbon::parse($tide['t'])->format('H:i');
            $level = (float) $tide['v'];

            if (!isset($tideDataByDate[$date])) {
                $tideDataByDate[$date] = [
                    'date' => $date,
                    'year' => Carbon::parse($date)->year,
                    'month' => Carbon::parse($date)->format('F'),
                    'high_tide_time' => null,
                    'high_tide_level' => null,
                    'low_tide_time' => null,
                    'low_tide_level' => null
                ];
            }

            if ($tideDataByDate[$date]['high_tide_level'] === null || $level > $tideDataByDate[$date]['high_tide_level']) {
                $tideDataByDate[$date]['high_tide_time'] = $time;
                $tideDataByDate[$date]['high_tide_level'] = $level;
            }

            if ($tideDataByDate[$date]['low_tide_level'] === null || $level < $tideDataByDate[$date]['low_tide_level']) {
                $tideDataByDate[$date]['low_tide_time'] = $time;
                $tideDataByDate[$date]['low_tide_level'] = $level;
            }
        }

        foreach ($tideDataByDate as $entry) {
            TideData::updateOrCreate(
                ['date' => $entry['date']],
                $entry
            );
        }

        $this->info("Tide data successfully fetched and stored for {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
    }
}
