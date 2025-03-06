<?php

namespace Tuna976\CustomCalendar\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Tuna976\CustomCalendar\Models\NOAATideForecast;

class FetchNOAAStationsCommand extends Command
{
    protected $signature = 'noaa:fetch-stations';
    protected $description = 'Fetch and store NOAA stations data';

    public function handle()
    {
        $response = Http::get("https://api.tidesandcurrents.noaa.gov/mdapi/prod/webapi/stations.json");

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch NOAA stations'], 500);
        }

        $data = $response->json();

        if (!isset($data['stations'])) {
            return response()->json(['error' => 'Invalid NOAA API response'], 500);
        }

        foreach ($data['stations'] as $station) {
            NOAAStation::updateOrCreate(
                ['station_id' => $station['id']],
                [
                    'name' => $station['name'],
                    'latitude' => $station['lat'],
                    'longitude' => $station['lng'],
                    'state' => $station['state'] ?? null,
                    'timezone' => $station['timezone'] ?? null,
                    'products' => json_encode($station['products']),
                ]
            );
        }

        return response()->json(['message' => 'NOAA stations updated successfully']);
    }

}
