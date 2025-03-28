<?php
use Illuminate\Support\Facades\Route;
use Tuna976\CustomCalendar\Http\Controllers\CalendarController;
use Livewire\Livewire;

Route::get('/moon-calendar', [CalendarController::class, 'showCalendar'])->name('moon-calendar');
Route::get('/moon-calendar/widget-table', [CalendarController::class, 'showCalendarWidgetTable'])->name('moon-calendar.widget-table');
Route::get('/moon-calendar/widget-live', [CalendarController::class, 'showCalendarWidgetLive'])->name('moon-calendar.widget-live');

Livewire::setUpdateRoute(function ($handle) {
    return Route::post('/livewire/update', $handle);
});
?>
