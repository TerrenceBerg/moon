<?php
use Illuminate\Support\Facades\Route;
use Tuna976\CustomCalendar\Http\Controllers\CalendarController;

Route::get('/moon-calendar', [CalendarController::class, 'showCalendar'])->name('moon-calendar');
?>
