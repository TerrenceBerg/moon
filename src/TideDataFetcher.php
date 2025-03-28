<?php

namespace Tuna976\CustomCalendar;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TideDataFetcher
{
    public function fetchTideData($lat, $lon, $datetime = null)
    {
        $datetime = $datetime
            ? Carbon::parse($datetime)->setTimeFromTimeString(now()->format('H:i:s'))
            : now();
        $station = $this->getNearestStation($lat, $lon);

        if (!$station) {
            throw new \Exception('No NOAA station found near the specified coordinates.');
        }

        $tideData = $this->getFullDayTideData($station['id'], $datetime);
        $classifiedTides = $this->classifyTides($tideData);
        $now = Carbon::now();

        $closestHighTide = $this->getClosestTideTime($classifiedTides, $now, 'High');
        $closestLowTide = $this->getClosestTideTime($classifiedTides, $now, 'Low');

        return [
            'station_id' => $station['id'],
            'station_name' => $station['name'],
            'datetime' => $datetime->toDateTimeString(),
            'high_tide_time' => $closestHighTide['time'],
            'low_tide_time' => $closestLowTide['time'],
            'high_tide_level' => $closestHighTide['level'],
            'low_tide_level' => $closestLowTide['level'],
        ];
    }

    protected function getNearestStation($lat, $lon)
    {
        $response = Http::get("https://api.tidesandcurrents.noaa.gov/mdapi/prod/webapi/stations.json");

        $stations = $response->json()['stations'] ?? [];

        usort($stations, function ($a, $b) use ($lat, $lon) {
            return $this->calculateDistance($lat, $lon, $a['lat'], $a['lng'])
                <=> $this->calculateDistance($lat, $lon, $b['lat'], $b['lng']);
        });

        return $stations[0] ?? null;
    }

    protected function getFullDayTideData($stationId, Carbon $datetime)
    {
        ini_set('max_execution_time', 10000);
//        dd($stationId);
        $begin = $datetime->copy()->startOfDay()->format('Ymd H:i');
        $end = $datetime->copy()->endOfDay()->format('Ymd H:i');
        $products = ['predictions'];
        $noaaApiUrl = "https://api.tidesandcurrents.noaa.gov/api/prod/datagetter";
        foreach ($products as $product) {
            $response = Http::get($noaaApiUrl, [
                'begin_date' => $begin,
                'end_date' => $end,
                'station' => $stationId,
                'product' => $product,
                'datum' => 'MLLW',
                'time_zone' => 'gmt',
                'interval' => 'hilo',
                'units' => 'metric',
                'format' => 'json'
            ]);
            return $response->json()['predictions'] ?? [];
        }

    }

    protected function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    protected function classifyTides(array $tideData): array
    {
        $classified = [];

        for ($i = 1; $i < count($tideData) - 1; $i++) {
            $prev = (float)$tideData[$i - 1]['v'];
            $curr = (float)$tideData[$i]['v'];
            $next = (float)$tideData[$i + 1]['v'];

            if ($curr > $prev && $curr > $next) {
                $tideData[$i]['type'] = 'High';
                $classified[] = $tideData[$i];
            } elseif ($curr < $prev && $curr < $next) {
                $tideData[$i]['type'] = 'Low';
                $classified[] = $tideData[$i];
            }
        }

        return $classified;
    }

    protected function getClosestTideTime(array $tideData, Carbon $datetime, string $type)
    {
        $filteredTideData = array_filter($tideData, fn($tide) => isset($tide['type']) && $tide['type'] === $type);

        $closestTide = null;
        $closestTimeDifference = PHP_INT_MAX;

        foreach ($filteredTideData as $tide) {
            $tideTime = Carbon::parse($tide['t']);
            $timeDifference = abs($datetime->diffInMinutes($tideTime));

            if ($timeDifference < $closestTimeDifference) {
                $closestTide = $tide;
                $closestTimeDifference = $timeDifference;
            }
        }

        return $closestTide
            ? [
                'time' => Carbon::parse($closestTide['t'])->toDateTimeString(),
                'level' => (float)$closestTide['v']
            ]
            : null;
    }
}
