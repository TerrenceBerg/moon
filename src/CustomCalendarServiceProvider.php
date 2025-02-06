<?php

namespace Tuna976\CustomCalendar;

use Illuminate\Support\ServiceProvider;

class CustomCalendarServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // ✅ Load Views (Ensure directory path is correct)
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'customcalendar');

        // ✅ Load Routes (Remove runningInConsole check)
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        }

        // ✅ Publish Config (Ensure correct path)
        $this->publishes([
            __DIR__.'/../Config/custom-calendar.php' => config_path('custom-calendar.php'),
        ], 'config');

        // ✅ Load Controllers (Ensure correct path)
        $this->loadControllers();
    }

    public function register()
    {
        // ✅ Merge Config (Ensure correct path)
        $this->mergeConfigFrom(__DIR__.'/../Config/customcalendar.php', 'customcalendar');

        // ✅ Bind Calendar Service
        $this->app->singleton('CustomCalendar', function ($app) {
            return new CustomCalendar();
        });
    }

    /**
     * Load the controllers in the package.
     */
    protected function loadControllers()
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php'); // Ensure that routes use the controller path
    }
}
