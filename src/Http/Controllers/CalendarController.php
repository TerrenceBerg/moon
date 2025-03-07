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
        $currentYear = now()->year;

        $ip = $request->ip();
        $location = $this->getLocationFromIP($ip);

        if (!$location) {
            return response()->json(['error' => 'Unable to determine location.'], 400);
        }

        // Step 3: Find Nearest NOAA Station
        $nearestStation = NOAAStation::getNearestStation($location['lat'], $location['lon']);

        if (!$nearestStation) {
            return response()->json(['error' => 'No station found.'], 404);
        }

        // Step 4: Generate Calendar Data Using the Nearest Station
        $calendarData = $this->calendarService->generateCalendar($currentYear, $nearestStation->station_id);

        return view('customcalendar::calendar', compact('calendarData', 'nearestStation'));
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
