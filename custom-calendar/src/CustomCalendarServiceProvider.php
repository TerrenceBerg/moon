<?php

namespace Tuna976\CustomCalendar;

use Illuminate\Support\ServiceProvider;

class CustomCalendarServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load Views
        $this->loadViewsFrom(__DIR__.'/Views', 'customcalendar');

        // Publish Config
        $this->publishes([
            __DIR__.'/config/customcalendar.php' => config_path('customcalendar.php'),
        ], 'config');

        // Load Routes
        if ($this->app->runningInConsole()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }
    }

    public function register()
    {
        // Merge Config
        $this->mergeConfigFrom(__DIR__.'/config/customcalendar.php', 'customcalendar');

        // Bind Calendar Service
        $this->app->singleton('CustomCalendar', function ($app) {
            return new CustomCalendar();
        });
    }
}
