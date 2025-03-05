<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responsive 13-Month Calendar</title>
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

        .calendar-grid-months {
            display: flex;
            flex-wrap: wrap;
            justify-content: center; /* Centers last row */
            gap: 20px;
        }

        .calendar-month {
            flex: 1 1 calc(33.333% - 20px); /* Three months per row */
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            min-width: 300px;
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

        .gregorian-date {
            font-size: 12px;
            color: #555;
            display: block;
            margin-top: 5px;
            font-weight: bold;
        }

        @media (max-width: 992px) {
            .calendar-month {
                flex: 1 1 calc(50% - 20px); /* Two months per row */
            }
        }

        @media (max-width: 768px) {
            .calendar-month {
                flex: 1 1 100%; /* One month per row */
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="calendar-container">
        <h1 class="text-center text-primary">13-Month Calendar</h1>
        <div class="accordion" id="yearAccordion">
            @php $currentYear = now()->year; @endphp
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
                                    <h6>Solar Events for {{ $year }}</h6>
                                    <p><strong>March Equinox:</strong> {{ $data['solar_events']['march_equinox'] }}</p>
                                    <p><strong>June Solstice:</strong> {{ $data['solar_events']['june_solstice'] }}</p>
                                    <p><strong>September
                                            Equinox:</strong> {{ $data['solar_events']['september_equinox'] }}</p>
                                    <p><strong>December
                                            Solstice:</strong> {{ $data['solar_events']['december_solstice'] }}</p>
                                </div>
                            @endif
                            <div class="calendar-grid-months">
                                @foreach ($data['months'] as $month)
                                    <div class="calendar-month">
                                        <h5 class="text-center bg-primary text-white p-2 rounded">{{ $month['name'] }}</h5>
                                        <div class="calendar-grid">
                                            <div class="day-header">Sun</div>
                                            <div class="day-header">Mon</div>
                                            <div class="day-header">Tue</div>
                                            <div class="day-header">Wed</div>
                                            <div class="day-header">Thu</div>
                                            <div class="day-header">Fri</div>
                                            <div class="day-header">Sat</div>
                                            @foreach($month['days'] as $i=>$day)
                                                <div class="calendar-day">
                                                    <span class="gregorian-date"> {{ $i+1 }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
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
