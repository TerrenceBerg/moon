<?php
namespace Tuna976\CustomCalendar\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Tuna976\CustomCalendar\CustomCalendar;
use Tuna976\CustomCalendar\Models\NOAAStation;
use Illuminate\Support\Facades\Http;

class CalendarController extends Controller
{
    protected $calendarService;

    public function __construct(CustomCalendar $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function showCalendar(Request $request)
    {
        $ip = $request->ip();
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $location = ['lat' => 34.0522, 'lon' => -118.2437]; // Default: Los Angeles, CA
        } else {
            $location = $this->getLocationFromIP($ip);
        }
        if (!$location) {
            return response()->json(['error' => 'Unable to determine location.'], 400);
        }
        $nearestStation = NOAAStation::getNearestStation($location['lat'], $location['lon']);

        if (!$nearestStation) {
            return response()->json(['error' => 'No station found.'], 404);
        }
            // Step 4: Generate Calendar Data Using the Nearest Station
//            $calendarData = $this->calendarService->generateCalendar($currentYear, $nearestStation->station_id);

        return view('customcalendar::calendar', compact( 'nearestStation'));
    }

    private function getLocationFromIP($ip)
    {
        $apiUrl = "https://ipapi.co/{$ip}/json/";

        $response = Http::get($apiUrl);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return [
            'lat' => $data['latitude'] ?? null,
            'lon' => $data['longitude'] ?? null,
            'city' => $data['city'] ?? 'Unknown'
        ];
    }
}
?>
