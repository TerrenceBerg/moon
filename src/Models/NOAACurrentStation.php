<?php

namespace Tuna976\CustomCalendar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NOAACurrentStation extends Model
{
    use HasFactory;
    protected $table='noaa_current_stations';

    protected $fillable = ['station_id', 'name', 'latitude', 'longitude', 'metadata'];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function currents()
    {
        return $this->hasMany(NoaaCurrent::class, 'station_id');
    }
}
