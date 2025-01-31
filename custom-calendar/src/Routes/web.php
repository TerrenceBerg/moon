<?php

use Illuminate\Support\Facades\Route;
use Tuna976\CustomCalendar\CustomCalendar;

Route::get('/custom-calendar', function (CustomCalendar $calendar) {
    $customCalendar = $calendar->generateCustomCalendar();
    return view('customcalendar::calendar', ['calendar' => $customCalendar]);
});
