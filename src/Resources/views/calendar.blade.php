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

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 3px;
            background: #e9ecef;
            padding: 10px;
            border-radius: 10px;
        }

        .calendar-day {
            background: white;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            font-size: 12px;
            border: 1px solid #dee2e6;
        }

        .green-day {
            background: #28a745;
            color: white;
            font-weight: bold;
        }

        .day-header {
            font-weight: bold;
            text-align: center;
            background: #343a40;
            color: white;
            padding: 5px;
            font-size: 12px;
            border-radius: 5px;
        }

        .date-info {
            font-size: 10px;
            color: #555;
            display: block;
            margin-top: 2px;
            font-weight: bold;
        }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .calendar-grid {
                grid-template-columns: repeat(7, minmax(25px, 1fr));
                font-size: 10px;
                padding: 5px;
            }

            .calendar-day {
                padding: 6px;
                font-size: 10px;
            }

            .nav-buttons {
                display: flex;
                justify-content: space-between;
                margin-top: 5px;
            }

            .btn-sm {
                padding: 5px 10px;
                font-size: 12px;
            }
        }
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles

</head>
<body>

<div class="container">
    <livewire:custom-calendar  />
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@livewireScripts

</body>
</html>
