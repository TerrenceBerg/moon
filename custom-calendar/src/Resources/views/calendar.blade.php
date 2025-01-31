<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Calendar</title>
</head>
<body>
<h1>Custom Calendar</h1>
<div>
    @foreach($calendar as $month => $data)
        <h2>Month {{ $month }}</h2>
        <ul>
            @foreach($data['days'] as $day)
                <li>{{ $day }}</li>
            @endforeach
        </ul>
    @endforeach
    <h2>{{ $calendar['rest_day'] }}</h2>
</div>
</body>
</html>
