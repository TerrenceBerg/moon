<?php
namespace Tuna976\CustomCalendar\Commands;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Tuna976\CustomCalendar\Models\NoaaCurrent;
use Tuna976\CustomCalendar\Models\NOAACurrentStation;
use Tuna976\CustomCalendar\Models\NOAAStation;


class MatchNoaaCurrentStations extends Command
{
    protected $signature = 'match:noaa-currents';
    protected $description = 'Fetch and store NOAA current data for all stations';

    public function handle()
    {
        $stations = NOAAStation::all();
        $currentStations = NoaaCurrentStation::all();

        foreach ($stations as $station) {
            $closest = $currentStations->filter(function ($current) use ($station) {
                $distance = $this->haversineGreatCircleDistance(
                    $station->latitude, $station->longitude,
                    $current->latitude, $current->longitude
                );
                return $distance < 50;
            })->sortBy(function ($current) use ($station) {
                return $this->haversineGreatCircleDistance(
                    $station->latitude, $station->longitude,
                    $current->latitude, $current->longitude
                );
            })->first();

            if ($closest) {
                $station->noaa_current_station_id = $closest->id;
                $station->save();
            }
        }
    }

    protected function haversineGreatCircleDistance($lat1, $lon1, $lat2, $lon2, $earthRadius = 6371)
    {
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}
