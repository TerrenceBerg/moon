<?php

namespace Tuna976\CustomCalendar\Http\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Tuna976\CustomCalendar\CustomCalendar;
use Tuna976\CustomCalendar\Models\NOAAStation;

class CalendarDayWidget extends Component
{
    public $dayData;
    public $currentDate;
    public $stationId,$location;
    public $widgetType='live';

    public function mount($widgetType,$date = null)
    {
        $this->currentDate = $date ?? now()->toDateString();


        $this->widgetType = $widgetType;
        if ($this->widgetType == 'live') {
            $this->loadDayDataLive();
        }
        elseif($this->widgetType == 'table') {
            $this->loadDayData();
        }

    }

    public function render()
    {
        return view('customcalendar::livewire.calendar-day-widget');
    }

    public function loadDayData()
    {
        $this->location = $this->getUserLocation();
        if (!$this->location) return response()->json(['error' => 'Unable to determine location.'], 400);

        $nearestStation = NOAAStation::getNearestStation($this->location['lat'], $this->location['lon']);
        if (!$nearestStation) return response()->json(['error' => 'No station found.'], 404);

        $this->stationId = $nearestStation->id ?? 1;

        $calendar = new CustomCalendar(null, $this->stationId);
        $this->dayData = $calendar->generateDayData($this->currentDate, $this->stationId);
    }
    public function loadDayDataLive()
    {
        $location=$this->getUserLocation();
        $this->location = $this->getUserLocation();
        $lat = $location['lat'];
        $lon = $location['lon'];
        $calendar = new CustomCalendar();
        $this->dayData = $calendar->generateDayDataLive($lat,$lon,$this->currentDate);
    }

    public function nextDate()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addDay()->toDateString();
        if ($this->widgetType == 'live') {
            $this->loadDayDataLive();
        }
        elseif($this->widgetType == 'table') {
            $this->loadDayData();
        }
    }

    public function previousDate()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subDay()->toDateString();
        if ($this->widgetType == 'live') {
            $this->loadDayDataLive();
        }
        elseif($this->widgetType == 'table') {
            $this->loadDayData();
        }
    }
    public function goToToday()
    {
        $this->currentDate = now()->toDateString();
        if ($this->widgetType == 'live') {
            $this->loadDayDataLive();
        }
        elseif($this->widgetType == 'table') {
            $this->loadDayData();
        }
    }
//    private function getUserLocation()
//    {
//        $ip = request()->ip();
//        if (in_array($ip, ['127.0.0.1', '::1'])) {
//            return ['lat' => 34.0522, 'lon' => -118.2437, 'city' => 'Los Angeles'];
//        }
//
//        try {
//            $response = Http::timeout(5)->get("https://ipapi.co/{$ip}/json");
//            if ($response->failed()) {
//                return null;
//            }
//            $data = $response->json();
//            return [
//                'lat' => $data['latitude'] ?? 0,
//                'lon' => $data['longitude'] ?? 0,
//                'city' => $data['city'] ?? 'Unknown'
//            ];
//        } catch (\Exception $e) {
//            \Log::error("Failed to fetch user location: " . $e->getMessage());
//            return null;
//        }
//    }

    private function getUserLocation()
    {
        $ip = request()->ip();

        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return [
                'lat' => 34.0522,
                'lon' => -118.2437,
                'city' => 'Los Angeles'
            ];
        }
        return cache()->remember("ip-location-{$ip}", now()->addDay(), function () use ($ip) {
            try {
                $response = Http::timeout(5)->get("https://ipwho.is/{$ip}");

                if ($response->failed() || !$response->json('success')) {
                    \Log::warning("IP lookup failed for IP: {$ip}");
                    return null;
                }

                $data = $response->json();

                return [
                    'lat' => $data['latitude'] ?? 0,
                    'lon' => $data['longitude'] ?? 0,
                    'city' => $data['city'] ?? 'Unknown'
                ];
            } catch (\Exception $e) {
                \Log::error("Failed to fetch user location for IP {$ip}: " . $e->getMessage());
                return null;
            }
        });
    }

}
