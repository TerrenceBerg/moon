<?php

namespace Tuna976\CustomCalendar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoaaCurrent extends Model
{
    use HasFactory;

    protected $fillable = ['station_id', 'timestamp', 'speed', 'direction', 'raw_data'];

    protected $casts = [
        'raw_data' => 'json',
    ];

    public function station()
    {
        return $this->belongsTo(NOAACurrentStation::class, 'station_id');
    }
}
