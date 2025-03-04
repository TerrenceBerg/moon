<?php

namespace Tuna976\CustomCalendar\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class ImportDataCommand extends Command
{
    protected $signature = 'calendar-data:import';
    protected $description = 'Import data from a data source';

    public function handle()
    {
        $jsonPath = __DIR__ . '/../../solstices_equinoxes.json';

        if (!File::exists($jsonPath)) {
            throw new \Exception("JSON file is missing.");
        }

        $data = json_decode(File::get($jsonPath), true);

        foreach ($data as $event) {
            DB::table('solar_events')->insert([
                'year' => $event['year'],

                // March Equinox
                'march_equinox' => Carbon::parse($event['march_equinox']),
                'march_days' => $event['march_days'],
                'march_hours' => $event['march_hours'],
                'march_minutes' => $event['march_minutes'],

                // June Solstice
                'june_solstice' => Carbon::parse($event['june_solstice']),
                'june_days' => $event['june_days'],
                'june_hours' => $event['june_hours'],
                'june_minutes' => $event['june_minutes'],

                // September Equinox
                'september_equinox' => Carbon::parse($event['september_equinox']),
                'september_days' => $event['september_days'],
                'september_hours' => $event['september_hours'],
                'september_minutes' => $event['september_minutes'],

                // December Solstice
                'december_solstice' => Carbon::parse($event['december_solstice']),
                'december_days' => $event['december_days'],
                'december_hours' => $event['december_hours'],
                'december_minutes' => $event['december_minutes'],

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info('Data imported successfully!');
    }
}
