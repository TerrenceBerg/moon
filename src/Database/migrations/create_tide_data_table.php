<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('tide_data', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('month');
            $table->date('date')->unique();
            $table->time('high_tide_time')->nullable();
            $table->decimal('high_tide_level', 5, 2)->nullable();
            $table->time('low_tide_time')->nullable();
            $table->decimal('low_tide_level', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tide_data');
    }
};
