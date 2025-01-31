<?php

namespace Tuna976\CustomCalendar;

use Illuminate\Support\ServiceProvider;

class CustomCalendarServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('custom-calendar', function () {
            return new CustomCalendar();
        });
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/Config/customcalendar.php' => config_path('customcalendar.php'),
        ], 'config');

        // Load routes (if any)
        if (file_exists(__DIR__.'/Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        }

        // Load views (optional for frontend)
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'customcalendar');
    }
}
