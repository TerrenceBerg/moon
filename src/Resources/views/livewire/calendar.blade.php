<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button wire:click="previous" class="btn btn-primary">&larr; Previous</button>
        <h3>
            @if ($viewMode === 'year')
                Year: {{ $currentDate->year }}
            @elseif ($viewMode === 'month')
                Month: {{ $currentDate->format('F Y') }} ({{ $lunarMonthNames[$currentDate->month] ?? '' }})
            @elseif ($viewMode === 'day')
                Day: {{ $currentDate->toFormattedDateString() }}
            @endif
        </h3>
        <button wire:click="next" class="btn btn-primary">Next &rarr;</button>
    </div>

    <div class="d-flex justify-content-center gap-2 mb-3">
        <button wire:click="changeView('year')" class="btn btn-outline-secondary">Year View</button>
        <button wire:click="changeView('month')" class="btn btn-outline-secondary">Month View</button>
        <button wire:click="changeView('day')" class="btn btn-outline-secondary">Day View</button>
    </div>

    @if ($viewMode === 'year')
        <h4>Yearly View: {{ $currentDate->year }}</h4>
        <div class="d-flex flex-wrap">
            @foreach ($monthsInYear as $moon)
                <div class="border text-center p-2 m-1" >
                    <strong>{{ $moon['name'] }}</strong>
                    <br>
                    <small>{{ $moon['days']->first()->format('M j Y') }} - {{ $moon['days']->last()->format('M j Y') }}</small>
                </div>
                <div class="d-flex flex-wrap">
                    @foreach ($daysInMonth as $day)
                        <div class="border text-center p-2 m-1" style="width: 60px;">
                            <strong>{{ $day['day_number'] }}</strong><br>
                            <small>{{ $day['date']->format('M j Y') }}</small>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    @if ($viewMode === 'month')
        <h4>Monthly View: {{ $lunarMonthNames[$currentDate->month] ?? '' }} ({{ $currentDate->format('F Y') }})</h4>
        <div class="d-flex flex-wrap">
            @foreach ($daysInMonth as $day)
                <div class="border text-center p-2 m-1" style="width: 60px;">
                    <strong>{{ $day['day_number'] }}</strong><br>
                    <small>{{ $day['date']->format('M j Y') }}</small>
                </div>
            @endforeach
        </div>
    @endif
    @if ($viewMode === 'day')
        <h4>Day View: {{ $lunarMonthNames[$currentLunarMonth] ?? 'Unknown Moon' }}</h4>
        <div class="border text-center p-3 m-1">
            <strong>{{ $currentDate->format('j') }}</strong><br>
            <small>{{ $currentDate->format('l, M j Y') }}</small>
        </div>
    @endif
</div>
