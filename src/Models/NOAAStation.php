<?php

namespace Tuna976\CustomCalendar\Models;

use Illuminate\Database\Eloquent\Model;

class NOAAStation extends Model
{
    protected $guarded = [];
    protected $table = 'noaa_stations';

    public static function getNearestStation($userLat, $userLon)
    {
        return self::selectRaw(
            "*, ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * 
            cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * 
            sin( radians( latitude ) ) ) ) AS distance",
            [$userLat, $userLon, $userLat]
        )
            ->orderBy('distance')
            ->first();
    }
}
