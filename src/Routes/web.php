<?php
use Illuminate\Support\Facades\Route;
use Tuna976\CustomCalendar\Http\Controllers\CalendarController;
use Livewire\Livewire;

Route::get('/moon-calendar', [CalendarController::class, 'showCalendar'])->name('moon-calendar');

Livewire::setUpdateRoute(function ($handle) {
    return Route::post('/livewire/update', $handle);
});
?>
