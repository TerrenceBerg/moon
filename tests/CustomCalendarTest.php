<?php

namespace Tuna976\CustomCalendar\Tests;

use Tuna976\CustomCalendar\CustomCalendar;
use PHPUnit\Framework\TestCase;

class CustomCalendarTest extends TestCase
{
    public function testGenerateCalendar()
    {
        $calendar = new CustomCalendar();
        $generatedCalendar = $calendar->generateCustomCalendar();
        $this->assertArrayHasKey(1, $generatedCalendar); // Check month 1 exists
    }

    public function testTidesAPI()
    {
        $calendar = new CustomCalendar();
        $tides = $calendar->getTides('9414290');
        $this->assertIsArray($tides);
    }

    public function testMoonPhaseAPI()
    {
        $calendar = new CustomCalendar();
        $moonPhase = $calendar->getMoonPhase('2025-01-31');
        $this->assertIsArray($moonPhase);
    }
}
