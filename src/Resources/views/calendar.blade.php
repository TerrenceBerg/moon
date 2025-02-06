<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Continuous Calendar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .calendar-container {
            max-width: 100%;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .months-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
        }

        .month-container {
            width: 100%; /* Takes up 48% of the row, leaving a small gap */
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .month-title {
            background-color: #007bff;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            background: #e9ecef;
            padding: 10px;
            border-radius: 10px;
        }

        .calendar-day {
            background: white;
            padding: 10px;
            border-radius: 5px;
            min-height: 100px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #dee2e6;
            position: relative;
        }

        .calendar-day:hover {
            background: #f8f9fa;
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

        .solunar {
            background: #ffeb3b;
            color: #000;
        }

        .tide {
            background: #17a2b8;
            color: #fff;
        }

        .moon {
            background: #6f42c1;
            color: #fff;
        }

        /* Responsive Layout */
        @media (max-width: 1024px) {
            .month-container {
                width: 100%; /* Display 2 months in one row */
            }
        }

        @media (max-width: 768px) {
            .month-container {
                width: 100%; /* Display 1 month per row on mobile */
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="calendar-container">
        <h1 class="text-center text-primary">13-Month Calendar</h1>

        <div class="months-row">
            @foreach ($calendarData as $year => $data)
                @foreach ($data['months'] as $month)
                    @php
                        $startDate = \Carbon\Carbon::parse($month['start_date']);
                        $endDate = \Carbon\Carbon::parse($month['end_date']);
                        $daysInMonth = $startDate->diffInDays($endDate) + 1;
                        $startDayOfWeek = $startDate->dayOfWeek;
                    @endphp

                    <div class="month-container">
                        <div class="month-title">{{ $month['name'] }} ({{ $year }})</div>
                        <div class="calendar-grid">
                            <!-- Day headers -->
                            <div class="day-header">Sun</div>
                            <div class="day-header">Mon</div>
                            <div class="day-header">Tue</div>
                            <div class="day-header">Wed</div>
                            <div class="day-header">Thu</div>
                            <div class="day-header">Fri</div>
                            <div class="day-header">Sat</div>

                            <!-- Empty cells for the first week -->
                            @for ($i = 0; $i < $startDayOfWeek; $i++)
                                <div class="calendar-day"></div>
                            @endfor

                            <!-- Actual dates with events -->
                            @for ($i = 0; $i < $daysInMonth; $i++)
                                @php
                                    $currentDate = $startDate->copy()->addDays($i)->toDateString();
                                    $dayNumber = $startDate->copy()->addDays($i)->format('d');
                                @endphp
                                <div class="calendar-day">
                                    <strong>{{ $dayNumber }}</strong>

                                    @if(isset($data['solunar'][$month['name']]))
                                        <div class="event solunar">
                                            🎣 {{ $data['solunar'][$month['name']]['best_fishing_time'] }}
                                        </div>
                                    @endif

                                    @if(isset($data['tides'][$month['name']]))
                                        <div class="event tide">
                                            🌊 HT: {{ $data['tides'][$month['name']]['high_tide'] }}<br>
                                            🌊 LT: {{ $data['tides'][$month['name']]['low_tide'] }}
                                        </div>
                                    @endif

                                    @if(isset($data['moon_phases'][$month['name']]))
                                        <div class="event moon">
                                            🌑 {{ $data['moon_phases'][$month['name']]['new_moon'] }}<br>
                                            🌕 {{ $data['moon_phases'][$month['name']]['full_moon'] }}
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
