<?php

namespace Tuna976\CustomCalendar\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Tuna976\CustomCalendar\CustomCalendar;

class CalendarController extends Controller
{
    protected $calendarService;

    public function __construct(CustomCalendar $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function showCalendar(Request $request)
    {
        $currentYear = now()->year;
        $calendarData = $this->calendarService->generateCalendar($currentYear);
        dd($calendarData);

        return view('customcalendar::calendar', compact('calendarData'));
    }
}
