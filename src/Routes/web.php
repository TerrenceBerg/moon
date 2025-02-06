<?php

use Illuminate\Support\Facades\Route;
use Tuna976\CustomCalendar\CustomCalendar;

Route::get('/moon-calendar', function (CustomCalendar $calendar) {
    $customCalendar = $calendar->generateCalendar();
    return view('customcalendar::calendar', ['calendar' => $customCalendar]);
});
