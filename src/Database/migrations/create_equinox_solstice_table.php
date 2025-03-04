<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solar_events', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();

            // March Equinox
            $table->dateTime('march_equinox');
            $table->integer('march_days');
            $table->integer('march_hours');
            $table->integer('march_minutes');

            // June Solstice
            $table->dateTime('june_solstice');
            $table->integer('june_days');
            $table->integer('june_hours');
            $table->integer('june_minutes');

            // September Equinox
            $table->dateTime('september_equinox');
            $table->integer('september_days');
            $table->integer('september_hours');
            $table->integer('september_minutes');

            // December Solstice
            $table->dateTime('december_solstice');
            $table->integer('december_days');
            $table->integer('december_hours');
            $table->integer('december_minutes');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solar_events');
    }
};
?>
