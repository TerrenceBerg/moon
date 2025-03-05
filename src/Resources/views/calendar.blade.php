<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>13-Month Calendar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            min-height: 100vh;
        }

        .calendar-container {
            max-width: 1300px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .calendar-month {
            width: 100%;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            background: #e9ecef;
            padding: 10px;
            border-radius: 10px;
            overflow-x: auto;
        }

        .calendar-day {
            background: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #dee2e6;
        }

        .day-header {
            font-weight: bold;
            text-align: center;
            background: #343a40;
            color: white;
            padding: 6px;
            border-radius: 5px;
        }

        .date-info {
            font-size: 12px;
            color: #555;
            display: block;
            margin-top: 5px;
            font-weight: bold;
        }

        .green-day {
            background: #28a745;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="calendar-container">
        <h1 class="text-center text-primary">13-Month Calendar</h1>
        <div class="accordion" id="yearAccordion">
            @php
                $currentYear = now()->year;
            @endphp
            @foreach ($calendarData as $year => $data)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-{{ $year }}">
                        <button class="accordion-button {{ $year == $currentYear ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $year }}"
                                aria-expanded="{{ $year == $currentYear ? 'true' : 'false' }}"
                                aria-controls="collapse-{{ $year }}">
                            Year {{ $year }}
                        </button>
                    </h2>
                    <div id="collapse-{{ $year }}"
                         class="accordion-collapse collapse {{ $year == $currentYear ? 'show' : '' }}"
                         aria-labelledby="heading-{{ $year }}" data-bs-parent="#yearAccordion">
                        <div class="accordion-body">
                            @if(isset($data['solar_events']))
                                <div class="solar-events">
                                    <h6 class="text-center">Solar Events for {{ $year }}</h6>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead class="table-dark text-center">
                                            <tr>
                                                <th>Event</th>
                                                <th>Date</th>
                                            </tr>
                                            </thead>
                                            <tbody class="text-center">
                                            <tr>
                                                <td><strong>March Equinox</strong></td>
                                                <td>{{ $data['solar_events']['march_equinox'] }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>June Solstice</strong></td>
                                                <td>{{ $data['solar_events']['june_solstice'] }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>September Equinox</strong></td>
                                                <td>{{ $data['solar_events']['september_equinox'] }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>December Solstice</strong></td>
                                                <td>{{ $data['solar_events']['december_solstice'] }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            <div class="row">
                                @foreach ($data['months'] as $month)
                                    <div class="calendar-month col-12">
                                        <h5 class="text-center bg-primary text-white p-2 rounded">{{ $month['name'] }}</h5>
                                        <div class="calendar-grid">
                                            <div class="day-header">Sun</div>
                                            <div class="day-header">Mon</div>
                                            <div class="day-header">Tue</div>
                                            <div class="day-header">Wed</div>
                                            <div class="day-header">Thu</div>
                                            <div class="day-header">Fri</div>
                                            <div class="day-header">Sat</div>
                                            @foreach($month['days'] as $i => $day)
                                                <div class="calendar-day">
                                                    <span class="gregorian-date"> {{ $i+1 }}</span>
                                                    <span class="date-info">{{ $day['gregorian_date'] }}</span>
                                                    <span class="date-info">{{ $day['julian_day'] }}</span>
                                                    <span class="date-info">{{ $day['moon_phase'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                @php
                                    $totalDays = array_sum(array_map(fn($m) => count($m['days']), $data['months']));
                                    $extraDays = ($data['is_leap_year'] ? 366 : 365) - $totalDays;
                                @endphp

                                @if ($extraDays > 0)
                                    <div class="calendar-month col-12">
                                        <h5 class="text-center bg-success text-white p-2 rounded">Extra Days</h5>
                                        <div class="calendar-grid">
                                            @for ($i = 1; $i <= $extraDays; $i++)
                                                <div class="calendar-day green-day">
                                                    <span class="gregorian-date">{{ $totalDays + $i }}</span>
                                                    <span class="date-info">Green Day</span>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                @endif

                            </div>
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
