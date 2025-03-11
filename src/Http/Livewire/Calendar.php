<?php

namespace Tuna976\CustomCalendar\Http\Livewire;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Tuna976\CustomCalendar\CustomCalendar;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Tuna976\CustomCalendar\Models\NOAATideForecast;

class Calendar extends Component
{
    public $stations;
    public $selectedStation;
    public $calendarData;
    public $loading = false;

    public $selectedDate;
    public $modalData;
    public $showModal = false;

    public function mount($stationId = null)
    {
        $this->stations = NOAAStation::all();
        $this->selectedStation = $stationId ?? $this->stations->first()->id;
        $this->loadCalendar();
    }

    public function loadCalendar()
    {
        $this->loading = true;
        $calendarService = new CustomCalendar(now()->year, $this->selectedStation);
        $this->calendarData = $calendarService->generateCalendar();
        $this->loading = false;
    }

    public function updatedSelectedStation()
    {
        $this->loadCalendar();
    }

    public function loadMoreData($date)
    {

        $this->selectedDate = $date;
        $this->modalData = NOAATideForecast::where('station_id', $this->selectedStation)
            ->whereDate('date', $date)
            ->first();
        if (!isset($this->modalData->sunrise) || !isset($this->modalData->sunset))
        {
            $this->storeSunriseSunsetData($date,$this->selectedStation);
            $this->modalData = NOAATideForecast::where('station_id', $this->selectedStation)
                ->whereDate('date', $date)
                ->first();
        }
        $this->modalData->moon_phase=$this->getMoonPhase($date);

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedDate = null;
        $this->modalData = null;
    }
    public function render()
    {
        return view('customcalendar::livewire.calendar', [
            'calendarData' => $this->calendarData
        ]);
    }
    private function getMoonPhase($date)
    {
        $synodicMonth = 29.53058867;
        $knownNewMoon = Carbon::create(2000, 1, 6, 18, 14, 0);
        $daysSinceNewMoon = $knownNewMoon->floatDiffInDays(Carbon::parse($date));

        $moonPhases = [
            'New Moon 🌑', 'Waxing Crescent 🌒', 'First Quarter 🌓',
            'Waxing Gibbous 🌔', 'Full Moon 🌕', 'Waning Gibbous 🌖',
            'Last Quarter 🌗', 'Waning Crescent 🌘'
        ];

        return $moonPhases[(int)round(($daysSinceNewMoon % $synodicMonth) / $synodicMonth * 8) % 8] ?? null;
    }

    private function storeSunriseSunsetData($date, $station_id)
    {
        try {
            $station=NOAAStation::find($station_id);
            $latitude = $station->latitude;
            $longitude = $station->longitude;

                $formattedDate = Carbon::parse($date)->format('Y-m-d');

                $response = Http::get("https://api.sunrise-sunset.org/json", [
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'formatted' => 0,
                    'date' => $formattedDate
                ]);

                $data = $response->json();
                // Convert UTC to PST
                $sunriseUTC = Carbon::parse($data['results']['sunrise']);
                $sunsetUTC = Carbon::parse($data['results']['sunset']);
                $pstTimeZone = new CarbonTimeZone('America/Los_Angeles');

                $sunrisePST = $sunriseUTC->setTimezone($pstTimeZone)->format('H:i');
                $sunsetPST = $sunsetUTC->setTimezone($pstTimeZone)->format('H:i');

                // Update or Create the record
                NOAATideForecast::updateOrCreate(
                    ['station_id' => $station->id, 'date' => $formattedDate],
                    ['sunrise' => $sunrisePST, 'sunset' => $sunsetPST]
                );
        } catch (\Exception $e) {
            \Log::error("Failed to fetch sunrise/sunset data: " . $e->getMessage());
        }
    }
}
