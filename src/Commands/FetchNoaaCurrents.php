<?php
namespace Tuna976\CustomCalendar\Commands;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Tuna976\CustomCalendar\Models\NoaaCurrent;
use Tuna976\CustomCalendar\Models\NOAACurrentStation;


class FetchNoaaCurrents extends Command
{
    protected $signature = 'fetch:noaa-currents';
    protected $description = 'Fetch and store NOAA current data for all stations';

    public function handle()
    {
        $stations = NOAACurrentStation::all();
        $date = Carbon::now()->format('Ymd');

        foreach ($stations as $station) {
            $url = "https://api.tidesandcurrents.noaa.gov/api/prod/datagetter";

            $response = Http::get($url, [
                'date' => 'today',
                'station' => $station->station_id,
                'product' => 'currents_predictions',
                'time_zone' => 'gmt',
                'units' => 'metric',
                'format' => 'json'
            ]);

            if (!$response->successful()) {
                $this->warn("Failed to fetch currents for station: {$station->station_id}");
                continue;
            }

            $data = $response->json();

            foreach ($data['current_predictions'] ?? [] as $entry) {
                NoaaCurrent::updateOrCreate(
                    [
                        'station_id' => $station->id,
                        'timestamp' => $entry['t'],
                    ],
                    [
                        'speed' => $entry['s'] ?? null,
                        'direction' => $entry['d'] ?? null,
                        'raw_data' => json_encode($entry)
                    ]
                );
            }

            $this->info("Stored current data for station: {$station->station_id}");
        }

        $this->info('All currents data fetched and stored.');
    }
}
