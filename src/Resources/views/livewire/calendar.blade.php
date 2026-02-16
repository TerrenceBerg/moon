<div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .full-page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(255, 255, 255, 0.7); /* semi-transparent white overlay */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* make sure it’s on top */
        }

        .loader-content {
            text-align: center;
        }
    </style>
    <!-- Fixed Header -->
    <div class="fixed-header shadow-sm">
        <div class="header-content container py-2">
            {{--            <h4 class="text-primary mb-1">--}}
            {{--                📍 Location:--}}
            {{--                <strong>{{ $location['city'] ?? 'N/A' }} ({{$location['lat']}},{{$location['lon']}})</strong>--}}
            {{--            </h4>--}}

            <h5 class="text-muted mb-2">
                🌍 Selected NOAA Station:
                <strong>{{ $selectedStation->name ?? 'N/A' }}</strong>
            </h5>
            <div>
                <label for="stationSelect" class="form-label fw-bold">Select Station:</label><br>
                <select id="stationSelect" wire:model="selectedStationId" class="form-control">
                    @foreach ($stations as $station)
                        <option value="{{ $station->id }}">
                            {{ $station->name }}
                        </option>
                    @endforeach
                </select>
            </div>


        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const stationSelect = document.getElementById('stationSelect');

            if (stationSelect) {
                stationSelect.addEventListener('change', function () {
                    const stationId = this.value;

                    // Dispatch or emit Livewire event
                    if (typeof Livewire.dispatch === 'function') {
                        Livewire.dispatch('updateStation', { stationId: parseInt(stationId) });
                    } else {
                        Livewire.emit('updateStation', parseInt(stationId));
                    }
                });
            }
        });

    </script>
    <!-- Spacer -->
    <div class="header-spacer mt-5"></div>

    <!-- Loading State -->
    <div wire:loading.delay class="full-page-loader">
        <div class="loader-content">
            <div class="spinner-border text-light" role="status"></div>
            <p class="mt-2 text-dark fw-bold">Fetching Data...</p>
        </div>
    </div>

    <!-- Calendar Content -->
    <div wire:loading.remove>
        <div class="calendar-container container py-4">
            <h1 class="text-center text-dark mb-4">📅 13-Month Calendar</h1>

            @foreach ($calendarData as $year => $data)
                <div class="accordion-item mb-4 border p-3 rounded shadow-sm">
                    <div class="row">
                        @foreach ($data['months'] as $index => $month)
                            <div class="calendar-month col-lg-12 mt-4" id="month-{{ $month['name'] }}">
                                <h5 class="text-center bg-primary text-black p-2 rounded">{{ $month['name'] }}</h5>
                                <div class="calendar-grid d-grid gap-1" style="grid-template-columns: repeat(7, 1fr);">
                                    @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                                        <div class="day-header fw-bold text-center">{{ $day }}</div>
                                    @endforeach

                                    @foreach($month['days'] as $i => $day)
                                        <div class="calendar-day p-2 border rounded text-center"
                                             id="day-{{ $day['date'] }}"
                                             style="{{ $day['is_today'] ? 'background-color: lightgreen; color: black; border: 2px solid green;' : '' }}">

                                            <!-- Desktop View (Hides on Mobile) -->
                                            <div class="d-none d-md-block">
                                                <span class="date-infoh">{{ $month['name'] }} Day {{$i+1}}</span>
                                                <hr>
                                                <span class="date-info d-block">{{ $day['moon_phase'] }}</span>


                                                {{-- Inline moon/tide info --}}
                                                @if (!empty($day['moon_data']))

                                                    <div class="text-center ">
                                                        {{-- Moon Age --}}
                                                        @if (!empty($day['moon_data']['age']))
                                                            <small>🕓 Age: {{ $day['moon_data']['age'] }}d</small><br>
                                                        @endif

                                                        {{-- Moon Phase --}}
                                                        @if (!empty($day['moon_data']['phase']))
                                                            <small>🌕 Phase: {{ $day['moon_data']['phase'] }}</small><br>
                                                        @endif

                                                        {{-- Distance --}}
                                                        @if (!empty($day['moon_data']['DI']))
                                                            <small>📏 Dist: {{ number_format($day['moon_data']['DI'], 1) }} ER</small><br>
                                                        @endif

                                                        {{-- Latitude --}}
                                                        @if (!empty($day['moon_data']['LA']))
                                                            <small>🧭 Lat: {{ number_format($day['moon_data']['LA'], 1) }}°</small><br>
                                                        @endif

                                                        {{-- Longitude --}}
                                                        @if (!empty($day['moon_data']['LO']))
                                                            <small>📐 Long: {{ number_format($day['moon_data']['LO'], 1) }}°</small>
                                                        @endif

                                                    </div>
                                                @endif
                                                <hr>
                                                <small><span class="gregorian-date" data-date="{{ \Carbon\Carbon::parse($day['gregorian_date'])->format('Y-m-d') }}">Gregorian Date<br>{{ \Carbon\Carbon::parse($day['gregorian_date'])->format('M-j-Y') }}</span></small>
                                                <span class="date-info">Julian Day {{ $day['julian_day'] }}</span>
                                                <hr>
                                                @if(isset($day['solunar_rating']))
                                                    @php
                                                        $rating = $day['solunar_rating'];
                                                    @endphp
                                                    <div class="my-2">
                                                        <small>Solunar Rating:</small><br>
                                                        @for ($star = 1; $star <= 4; $star++)
                                                            @if ($rating >= $star)
                                                                <i class="bi bi-star-fill text-warning"></i>
                                                            @elseif ($rating >= $star - 0.5)
                                                                <i class="bi bi-star-half text-warning"></i>
                                                            @else
                                                                <i class="bi bi-star text-muted"></i>
                                                            @endif
                                                        @endfor
                                                    </div>
                                                @endif
                                                @php $tide = $day['tide_data']; @endphp
                                                @if ($tide)
                                                    <hr><span><strong><small>Tides</small></strong></span>
                                                    <span class="date-info">🌊 High: {{ !empty($tide['high_tide_time']) && $tide['high_tide_time'] !== 'N/A' ? \Carbon\Carbon::parse($tide['high_tide_time'])->format('g:i a') : 'N/A' }} ({{ $tide['high_tide_level'] }}m)</span>
                                                    <span class="date-info">🌊 Low: {{ !empty($tide['low_tide_time']) && $tide['low_tide_time'] !== 'N/A' ? \Carbon\Carbon::parse($tide['low_tide_time'])->format('g:i a') : 'N/A' }} ({{ $tide['low_tide_level'] }}m)</span>
                                                @endif
                                                <button class="btn btn-sm btn-light mt-2 border border-dark"
                                                        wire:click="loadMoreData('{{ $day['date'] }}')">
                                                    <i class="bi bi-backpack4 text-primary"></i> More Info
                                                </button>
                                            </div>

                                            <!-- Mobile View -->
                                            <div class="d-block d-md-none">
                                                <a style="cursor: pointer; font-size: 10px; font-weight: bold"
                                                   href="#" class="text-dark"
                                                   wire:click.prevent="loadMoreData('{{ $day['date'] }}')">
                                                    {{ $i + 1 }}
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down" role="document">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header text-white" style="background: linear-gradient(to right, #1e3c72, #2a5298);">
                        <h5 class="modal-title">🌙 Astronomical Data for {{ $selectedDate }}</h5>
                        <button type="button" class="btn-close" wire:click.prevent="closeModal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @if($solunarData)
                            <div class="card rounded border-dark mb-3 p-3">
                                <h5 class="fw-bold mb-3 border-bottom pb-2">🌤️ Solunar Summary — {{ \Carbon\Carbon::parse($selectedDate)->format('M j, Y') }}</h5>
                                <div class="row small text-muted">
                                    <!-- Sun Info -->
                                    <div class="col-md-4 mb-2">
                                        <h6 class="text-dark mb-1">🌞 Sun</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li><strong>Rise:</strong> {{ $solunarData['sunRise'] }}</li>
                                            <li><strong>Transit:</strong> {{ $solunarData['sunTransit'] }}</li>
                                            <li><strong>Set:</strong> {{ $solunarData['sunSet'] }}</li>
                                        </ul>
                                    </div>

                                    <!-- Moon Info -->
                                    <div class="col-md-4 mb-2">
                                        <h6 class="text-dark mb-1">🌙 Moon</h6>
                                        <ul class="list-unstyled small mb-0">
                                            <li><strong>Rise:</strong> {{ $solunarData['moonRise'] ?? 'N/A' }}</li>
                                            <li><strong>Transit:</strong> {{ $solunarData['moonTransit'] ?? 'N/A' }}</li>
                                            <li><strong>Set:</strong> {{ $solunarData['moonSet'] ?? 'N/A' }}</li>
                                            <li><strong>Phase:</strong> {{ $solunarData['moonPhase'] ?? 'N/A' }}</li>
                                            <li><strong>Illumination:</strong> {{ isset($solunarData['moonIllumination']) ? round($solunarData['moonIllumination'] * 100) . '%' : 'N/A' }}</li>
                                        </ul>
                                    </div>

                                    <!-- Ratings -->
                                    <div class="col-md-4 mb-2">
                                        <h6 class="text-dark mb-1">🎯 Ratings</h6>
                                        <ul class="list-unstyled mb-2">
                                            <li><strong>Day:</strong> {{ $solunarData['dayRating'] }}</li>
                                            <li><strong>Calc:</strong> {{ $solunarData['calculatedRating'] }}</li>
                                        </ul>
                                        <h6 class="text-dark mb-1">🎣 Activity</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li><strong>Minor 1:</strong> {{ $solunarData['minor1Start'] }} – {{ $solunarData['minor1Stop'] }}</li>
                                            <li><strong>Minor 2:</strong> {{ $solunarData['minor2Start'] }} – {{ $solunarData['minor2Stop'] }}</li>
                                            <li><strong>Major 1:</strong> {{ $solunarData['major1Start'] }} – {{ $solunarData['major1Stop'] }}</li>
                                            <li><strong>Major 2:</strong> {{ $solunarData['major2Start'] }} – {{ $solunarData['major2Stop'] }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">No solunar data available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
