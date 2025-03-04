<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Continuous Calendar with Gregorian Dates</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .calendar-container {
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 5px;
            background: #e9ecef;
            padding: 10px;
            border-radius: 10px;
        }
        .calendar-day {
            background: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #dee2e6;
            position: relative;
        }
        .day-header {
            font-weight: bold;
            text-align: center;
            background: #343a40;
            color: white;
            padding: 6px;
            border-radius: 5px;
        }
        .event {
            font-size: 12px;
            margin-top: 5px;
            padding: 3px;
            border-radius: 5px;
        }
        .solar-events {
            background: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #d1e7ff;
            margin-bottom: 10px;
        }
        .solar-events h6 {
            font-weight: bold;
            color: #0056b3;
        }
        .moon { background: #6f42c1; color: #fff; }
        .gregorian-date {
            font-size: 12px;
            color: #555;
            display: block;
            margin-top: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="calendar-container">
        <h1 class="text-center text-primary">13-Month Calendar with Gregorian Dates</h1>
        <div class="accordion" id="yearAccordion">
            @php $currentYear = now()->year; @endphp
            @foreach ($calendarData as $year => $data)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-{{ $year }}">
                        <button class="accordion-button {{ $year == $currentYear ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $year }}" aria-expanded="{{ $year == $currentYear ? 'true' : 'false' }}" aria-controls="collapse-{{ $year }}">
                            Year {{ $year }}
                        </button>
                    </h2>
                    <div id="collapse-{{ $year }}" class="accordion-collapse collapse {{ $year == $currentYear ? 'show' : '' }}" aria-labelledby="heading-{{ $year }}" data-bs-parent="#yearAccordion">
                        <div class="accordion-body">
                            @if(isset($data['solar_events']))
                                <div class="solar-events">
                                    <h6>Solar Events for {{ $year }}</h6>
                                    <p><strong>March Equinox:</strong> {{ $data['solar_events']['march_equinox'] }}</p>
                                    <p><strong>June Solstice:</strong> {{ $data['solar_events']['june_solstice'] }}</p>
                                    <p><strong>September Equinox:</strong> {{ $data['solar_events']['september_equinox'] }}</p>
                                    <p><strong>December Solstice:</strong> {{ $data['solar_events']['december_solstice'] }}</p>
                                </div>
                            @endif
                            @foreach ($data['months'] as $month)
                                <div class="mb-4 p-3 border rounded bg-white shadow-sm">
                                    <h5 class="text-center bg-primary text-white p-2 rounded">{{ $month['name'] }} ({{ $year }})</h5>
                                    <div class="calendar-grid">
                                        @foreach($month['days'] as $day)
                                            <div class="calendar-day">
                                                <span class="day-header">{{ $day['day_of_week'] }}</span>
                                                <span class="gregorian-date">{{ $day['gregorian_date'] }}</span>
                                                <span class="gregorian-date">Julian: {{ $day['julian_day'] }}</span>
                                                @if(isset($day['moon_phase']))
                                                    <div class="event moon">{{ $day['moon_phase'] }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
