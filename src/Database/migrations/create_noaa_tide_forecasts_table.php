<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('noaa_tide_forecasts', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('month');
            $table->date('date');

            // Tide Data
            $table->time('high_tide_time')->nullable();
            $table->decimal('high_tide_level', 5, 2)->nullable();
            $table->time('low_tide_time')->nullable();
            $table->decimal('low_tide_level', 5, 2)->nullable();

            // Additional NOAA Data
            $table->decimal('water_temperature', 5, 2)->nullable(); // Sea temperature (°C)
            $table->decimal('wind_speed', 5, 2)->nullable(); // Wind speed (m/s)
            $table->string('wind_direction')->nullable(); // Wind direction (e.g., N, SE)
            $table->decimal('air_pressure', 6, 2)->nullable(); // Atmospheric pressure (hPa)

            // Sun & Moon Data
            $table->time('sunrise')->nullable();
            $table->time('sunset')->nullable();
            $table->string('moon_phase')->nullable(); // Moon phase name (e.g., Full Moon)


            $table->integer('station_id');


            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('noaa_tide_forecasts');
    }
};
