<?php

namespace Tuna976\CustomCalendar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NOAAEnvironmentalReading extends Model
{
    use HasFactory;

    protected $guarded=[];
    protected $table='noaa_environmental_readings';

    public function station()
    {
        return $this->belongsTo(NOAAStation::class, 'station_id');
    }
}
