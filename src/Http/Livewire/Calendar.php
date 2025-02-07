<?php

namespace Tuna976\CustomCalendar\Http\Livewire;

use Livewire\Component;
use Carbon\Carbon;

class Calendar extends Component
{
    public Carbon $currentDate;
    public string $viewMode = 'month'; // Default view mode
    protected array $moons = [];

    public function mount()
    {
        $this->currentDate = Carbon::now();
        $this->moons = $this->defineMoons();
    }

    public function next()
    {
        match ($this->viewMode) {
            'year' => $this->currentDate->addYear(),
            'month' => $this->moveToNextMoon(),
            'day' => $this->currentDate->addDay(),
        };
    }

    public function previous()
    {
        match ($this->viewMode) {
            'year' => $this->currentDate->subYear(),
            'month' => $this->moveToPreviousMoon(),
            'day' => $this->currentDate->subDay(),
        };
    }

    public function changeView(string $mode)
    {
        $this->viewMode = $mode;
    }

    public function render()
    {
        return view('customcalendar::livewire.calendar', [
            'daysInMonth' => $this->generateMonthView(),
            'monthsInYear' => $this->generateYearView(),
            'lunarMonthNames' => $this->getLunarMonthNames(),
        ]);
    }

    private function defineMoons(): array
    {
        return [
            ['name' => 'Magnetic Moon', 'number' => 1, 'offset' => 0],
            ['name' => 'Lunar Moon', 'number' => 2, 'offset' => 28],
            ['name' => 'Electric Moon', 'number' => 3, 'offset' => 56],
            ['name' => 'Self-Existing Moon', 'number' => 4, 'offset' => 84],
            ['name' => 'Overtone Moon', 'number' => 5, 'offset' => 112],
            ['name' => 'Rhythmic Moon', 'number' => 6, 'offset' => 140],
            ['name' => 'Resonant Moon', 'number' => 7, 'offset' => 168],
            ['name' => 'Galactic Moon', 'number' => 8, 'offset' => 196],
            ['name' => 'Solar Moon', 'number' => 9, 'offset' => 224],
            ['name' => 'Planetary Moon', 'number' => 10, 'offset' => 252],
            ['name' => 'Spectral Moon', 'number' => 11, 'offset' => 280],
            ['name' => 'Crystal Moon', 'number' => 12, 'offset' => 308],
            ['name' => 'Cosmic Moon', 'number' => 13, 'offset' => 336],
        ];
    }

    private function getCurrentMoon(): array
    {
        $this->moons = $this->defineMoons();
        if (empty($this->moons)) {
            throw new \Exception("Moon phases data is missing. Ensure moons are defined.");
        }

        $startOfYear = Carbon::create($this->currentDate->year, 7, 26);

        foreach ($this->moons as $moon) {
            $moonStart = $startOfYear->copy()->addDays($moon['offset']);
            $moonEnd = $moonStart->copy()->addDays(27); // Each moon lasts 28 days

            if ($this->currentDate->between($moonStart, $moonEnd)) {
                return $moon;
            }
        }

        // Default to the first moon if none found
        return $this->moons[0] ?? ['name' => 'Unknown Moon', 'offset' => 0];
    }

    private function moveToNextMoon()
    {
        $currentMoon = $this->getCurrentMoon();
        $nextMoon = $this->moons[$currentMoon['number']] ?? null;

        if ($nextMoon) {
            $this->currentDate = Carbon::create($this->currentDate->year, 7, 26)->addDays($nextMoon['offset']);
        } else {
            $this->currentDate = Carbon::create($this->currentDate->year + 1, 7, 26);
        }
    }

    private function moveToPreviousMoon()
    {
        $currentMoon = $this->getCurrentMoon();
        $prevMoon = $this->moons[$currentMoon['number'] - 2] ?? null;

        if ($prevMoon) {
            $this->currentDate = Carbon::create($this->currentDate->year, 7, 26)->addDays($prevMoon['offset']);
        } else {
            $this->currentDate = Carbon::create($this->currentDate->year - 1, 7, 26)->addDays(336);
        }
    }

    private function generateMonthView(): \Illuminate\Support\Collection
    {
        $currentMoon = $this->getCurrentMoon();
        $startOfYear = Carbon::create($this->currentDate->year, 7, 26);
        $startOfMoon = $startOfYear->copy()->addDays($currentMoon['offset']);

        return collect(range(1, 28))->map(fn($day) => [
            'day_number' => $day,
            'date' => $startOfMoon->copy()->addDays($day - 1),
        ]);
    }
    private function generateYearView(): \Illuminate\Support\Collection
    {
        $startOfYear = Carbon::create($this->currentDate->year, 7, 26); // Start of 13-moon year

        return collect($this->moons)->map(function ($moon) use ($startOfYear) {
            $startOfMoon = $startOfYear->copy()->addDays($moon['offset']);

            return [
                'name' => $moon['name'],
                'days' => collect(range(1, 28))->map(fn ($day) => $startOfMoon->copy()->addDays($day - 1)),
            ];
        });
    }

    private function getLunarMonthNames(): array
    {
        return array_column($this->moons, 'name', 'number');
    }
}
