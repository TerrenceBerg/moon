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

    public function mount($widgetType,$date = null)
    {
        $this->location = $this->getUserLocation();
        if (!$this->location) return response()->json(['error' => 'Unable to determine location.'], 400);

        $nearestStation = NOAAStation::getNearestStation($this->location['lat'], $this->location['lon']);
        if (!$nearestStation) return response()->json(['error' => 'No station found.'], 404);

        $this->stationId = $nearestStation->id ?? 1;
        $this->currentDate = $date ?? now()->toDateString();

        $this->currentDate = now()->toDateString();
        if ($widgetType == 'live') {
            $this->loadDayDataLive();
        }
        elseif($widgetType == 'table') {
            $this->loadDayData();
        }

    }

    public function render()
    {
        return view('customcalendar::livewire.calendar-day-widget');
    }

    public function loadDayData()
    {
        $calendar = new CustomCalendar(null, $this->stationId);
        $this->dayData = $calendar->generateDayData($this->currentDate, $this->stationId);
    }
    public function loadDayDataLive()
    {
        $location=$this->getUserLocation();
        $lat = $location['lat'];
        $lon = $location['lon'];
        $calendar = new CustomCalendar();
        $this->dayData = $calendar->generateDayDataLive($lat,$lon,$this->currentDate);
    }

    public function nextDate()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addDay()->toDateString();
        $this->loadDayData();
    }

    public function previousDate()
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subDay()->toDateString();
        $this->loadDayData();
    }
    public function goToToday()
    {
        $this->currentDate = now()->toDateString();
        $this->loadDayData();
    }
    private function getUserLocation()
    {
        $ip = request()->ip();
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return ['lat' => 34.0522, 'lon' => -118.2437, 'city' => 'Los Angeles'];
        }

        try {
            $response = Http::timeout(5)->get("https://ipapi.co/{$ip}/json");
            if ($response->failed()) {
                return null;
            }
            $data = $response->json();
            return [
                'lat' => $data['latitude'] ?? 0,
                'lon' => $data['longitude'] ?? 0,
                'city' => $data['city'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            \Log::error("Failed to fetch user location: " . $e->getMessage());
            return null;
        }
    }

}
