<?php

namespace Tuna976\CustomCalendar\Facades;

use Illuminate\Support\Facades\Facade;

class CustomCalendar extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'custom-calendar';
    }
}
