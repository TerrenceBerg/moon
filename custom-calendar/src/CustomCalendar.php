<?php

namespace Tuna976\CustomCalendar;

use Carbon\Carbon;
use GuzzleHttp\Client;

class CustomCalendar
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();  // Use Guzzle Client
    }

    // Custom Calendar Logic - 13 months, 28 days
    public function generateCustomCalendar($year = null)
    {
        $year = $year ?: Carbon::now()->year;
        $calendar = [];

        // Generate 13 months, 28 days each, with 1 rest day
        for ($month = 1; $month <= 13; $month++) {
            $calendar[$month] = [
                'month' => $month,
                'days' => $this->generateMonthDays($month)
            ];
        }

        // Add the rest day after the 13th month
        $calendar['rest_day'] = 'Rest Day (365th Day of Year)';

        return $calendar;
    }

    // Generate days for each month
    private function generateMonthDays($month)
    {
        $days = [];
        for ($day = 1; $day <= 28; $day++) {
            $days[] = $this->generateDate($month, $day);
        }
        return $days;
    }

    // Format the date
    private function generateDate($month, $day)
    {
        $year = Carbon::now()->year;
        return Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
    }

    // NOAA Tides API Integration
    public function getTides($stationId)
    {
        $response = $this->client->get("https://api.tidesandcurrents.noaa.gov/mdapi/prod/webapi/stations/{$stationId}/tidepredictions.json");
        return json_decode($response->getBody()->getContents(), true);
    }

    // Get Moon Phase API Integration
    public function getMoonPhase($date)
    {
        $response = $this->client->get("https://api.solunar.org/solunar/40.7128,-74.0060,{$date},-5");
        return json_decode($response->getBody()->getContents(), true);
    }
}
