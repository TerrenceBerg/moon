<?php

namespace Tuna976\CustomCalendar\Http\Livewire;

use Livewire\Component;
use Tuna976\CustomCalendar\CustomCalendar;

class CalendarDayWidget extends Component
{
    public $dayData;
    public $date;
    public $stationId;

    public function mount($date = null, $stationId = null)
    {
        $this->date = $date ?? now()->toDateString();
        $this->stationId = $stationId ?? 1; // Default station

        $calendar = new CustomCalendar();
        $this->dayData = $calendar->generateDayData($this->date, $this->stationId);
//        dd($this->dayData);
    }

    public function render()
    {
        return view('customcalendar::livewire.calendar-day-widget');
    }
}
