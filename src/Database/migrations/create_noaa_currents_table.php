<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('noaa_currents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id');
            $table->timestamp('timestamp');
            $table->decimal('speed', 8, 3)->nullable();
            $table->decimal('direction', 8, 3)->nullable();
            $table->json('raw_data')->nullable(); // Stores full API response
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('noaa_currents');
    }
};
