<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('noaa_environmental_readings', function (Blueprint $table) {
            $table->id();
            $table->string('station_id');
            $table->string('product');
            $table->timestamp('reading_time');
            $table->date('date');
            $table->string('value');
            $table->timestamps();

//            $table->unique(['station_id', 'product', 'reading_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('noaa_environmental_readings');
    }
};
