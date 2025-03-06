<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('noaa_stations', function (Blueprint $table) {
            $table->id();
            $table->string('station_id')->unique();
            $table->string('name');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->string('state')->nullable();
            $table->string('timezone')->nullable();
            $table->json('products')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('noaa_stations');
    }
};
