<?php
use Tuna976\CustomCalendar\Facades\CustomCalendar;

Route::get('/custom-calendar', function () {
    $year = request('year', date('Y'));
    $calendar = CustomCalendar::generate();
    return view('customcalendar::calendar', compact('calendar', 'year'));
});
