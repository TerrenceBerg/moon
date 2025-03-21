<?php
namespace Tuna976\CustomCalendar\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Tuna976\CustomCalendar\Models\NOAACurrentStation;

class FetchNOAACurrentStations extends Command
{
    protected $signature = 'fetch:noaa-current-stations';
    protected $description = 'Fetch and store NOAA Current stations data';

    public function handle()
    {
        $url = "https://api.tidesandcurrents.noaa.gov/mdapi/prod/webapi/stations.json?type=currents";
        $response = Http::get($url);

        if (!$response->successful()) {
            $this->error("Failed to fetch NOAA stations.");
            return;
        }

        $stationsData = $response->json();

        if (!isset($stationsData['stations'])) {
            $this->error("No stations data found.");
            return;
        }

        foreach ($stationsData['stations'] as $station) {
            NOAACurrentStation::updateOrCreate(
                ['station_id' => $station['id']],
                [
                    'name' => $station['name'] ?? 'Unknown',
                    'latitude' => $station['lat'] ?? null,
                    'longitude' => $station['lng'] ?? null,
                    'metadata' => json_encode($station)
                ]
            );
        }

        $this->info("NOAA stations data has been successfully stored.");
    }
}
