<div class="m-2">
    <div class="shadow-sm mb-3">
        <div class="header-content container py-3">
            <h4 class="text-dark mb-1">
                📍 Location: <strong>{{ $location['city'] ?? '' }}</strong>
            </h4>
            <div class="row">
                <div class="mt-2">
                    <label for="stationSelect" class="form-label fw-bold">Select Station:</label>
                    <select wire:model="selectedStation" id="stationSelect" class="form-control">
                        @foreach ($stations as $station)
                            <option value="{{ $station->id }}">{{ $station->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div wire:loading.delay class="full-page-loader">
        <div class="loader-content">
            <div class="spinner-border text-light" role="status"></div>
            <p class="mt-2 text-white fw-bold">Fetching Data...</p>
        </div>
    </div>

    <div wire:loading.remove>
        <div>
            <h1 class="text-center text-dark">📅 13-Month Calendar</h1>
            <div>
                @foreach ($calendarData as $year => $data)
                    <div>
                        {{--                        <h2 class="text-center" id="heading-{{ $year }}">--}}
                        {{--                            Year {{ $year }}--}}
                        {{--                        </h2>--}}
                        <div class="row">
                            @foreach ($data['months'] as $index=>$month)
                                <div class="calendar-month col-lg-12 col-md-12 col-sm-12 mt-4"
                                     id="month-{{ $month['name'] }}">
                                    <h5 class="text-center bg-primary text-dark p-2 rounded">{{ $month['name'] }}</h5>
                                    <div class="calendar-grid">
                                        <div class="day-header">Sun</div>
                                        <div class="day-header">Mon</div>
                                        <div class="day-header">Tue</div>
                                        <div class="day-header">Wed</div>
                                        <div class="day-header">Thu</div>
                                        <div class="day-header">Fri</div>
                                        <div class="day-header">Sat</div>
                                        @foreach($month['days'] as $i=>$day)
                                            <div class="calendar-day"
                                                 id="day-{{ $day['date'] }}"
                                                 style="{{ $day['is_today'] ? 'background-color: lightgreen; color: black; border: 2px solid green;' : '' }}">
                                                <!-- Desktop View (Hides on Mobile) -->
                                                <div class="d-none d-md-block">
                                                    <span class="date-infoh">{{ $month['name'] }} Day {{++$i}}</span>
                                                    <hr>
                                                    <span class="date-info d-block">{{ $day['moon_phase'] }}</span>


                                                    {{-- Inline moon/tide info --}}
                                                    @if (!empty($day['moon_data']))

                                                        <div class="text-center ">
                                                            {{-- Moon Age --}}
                                                            @if (!empty($day['moon_data']['age']))
                                                                <small>🕓 Age: {{ $day['moon_data']['age'] }}d</small>
                                                                <br>
                                                            @endif

                                                            {{-- Moon Phase --}}
                                                            @if (!empty($day['moon_data']['phase']))
                                                                <small>🌕 Phase: {{ $day['moon_data']['phase'] }}</small>
                                                                <br>
                                                            @endif

                                                            {{-- Distance --}}
                                                            @if (!empty($day['moon_data']['DI']))
                                                                <small>📏
                                                                    Dist: {{ number_format($day['moon_data']['DI'], 1) }}
                                                                    ER</small><br>
                                                            @endif

                                                            {{-- Latitude --}}
                                                            @if (!empty($day['moon_data']['LA']))
                                                                <small>🧭
                                                                    Lat: {{ number_format($day['moon_data']['LA'], 1) }}
                                                                    °</small><br>
                                                            @endif

                                                            {{-- Longitude --}}
                                                            @if (!empty($day['moon_data']['LO']))
                                                                <small>📐
                                                                    Long: {{ number_format($day['moon_data']['LO'], 1) }}
                                                                    °</small>
                                                            @endif

                                                        </div>
                                                    @endif
                                                    <hr>
                                                    <small><span class="gregorian-date"
                                                                 data-date="{{ \Carbon\Carbon::parse($day['gregorian_date'])->format('Y-m-d') }}">Gregorian Date<br>{{ \Carbon\Carbon::parse($day['gregorian_date'])->format('M-j-Y') }}</span></small>
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
                                                <!-- Mobile View (Hides on Desktop) -->
                                                <div class="d-block d-md-none text-center">
                                                    <a style="cursor: pointer; font-size: 10px; font-weight: bold"
                                                       href="" class="text-dark"
                                                       wire:click.prevent="loadMoreData('{{ $day['date'] }}')">
                                                        {{$i+1}}
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <hr class="my-3 border border-black">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog"
             style="background: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down" role="document">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    @if ($modalData)
                        <div class="modal-header bg-gradient text-white"
                             style="background: linear-gradient(to right, #1e3c72, #2a5298);">
                            <h5 class="modal-title text-white">🌙 Astronomical Data for {{ $selectedDate }}</h5>
                            <button type="button" class="btn-close" wire:click.prevent="closeModal"></button>
                        </div>
                        <div class="modal-body p-4">
                            @if($solunarData)
                                <div class="container card rounded border-dark mb-3 p-3">
                                    <h5 class="fw-bold mb-3 border-bottom pb-2">🌤️ Solunar Summary
                                        — {{ \Carbon\Carbon::parse($selectedDate)->format('M j, Y') }}</h5>
                                    <div class="row text-sm text-muted">

                                        {{-- Sun Info --}}
                                        <div class="col-md-4 mb-2">
                                            <h6 class="text-dark mb-1">🌞 Sun</h6>
                                            <ul class="list-unstyled small mb-0">
                                                <li><strong>Rise:</strong> {{ $solunarData['sunRise'] ?? 'N/A' }}</li>
                                                <li><strong>Transit:</strong> {{ $solunarData['sunTransit'] ?? 'N/A' }}
                                                </li>
                                                <li><strong>Set:</strong> {{ $solunarData['sunSet'] ?? 'N/A' }}</li>
                                            </ul>
                                        </div>

                                        {{-- Moon Info --}}
                                        <div class="col-md-4 mb-2">
                                            <h6 class="text-dark mb-1">🌙 Moon</h6>
                                            <ul class="list-unstyled small mb-0">
                                                <li><strong>Rise:</strong> {{ $solunarData['moonRise'] ?? 'N/A' }}</li>
                                                <li><strong>Transit:</strong> {{ $solunarData['moonTransit'] ?? 'N/A' }}
                                                </li>
                                                <li><strong>Set:</strong> {{ $solunarData['moonSet'] ?? 'N/A' }}</li>
                                                <li><strong>Phase:</strong> {{ $solunarData['moonPhase'] ?? 'N/A' }}
                                                </li>
                                                <li>
                                                    <strong>Illumination:</strong> {{ isset($solunarData['moonIllumination']) ? round($solunarData['moonIllumination'] * 100) . '%' : 'N/A' }}
                                                </li>
                                            </ul>
                                        </div>

                                        {{-- Ratings & Activity --}}
                                        <div class="col-md-4 mb-2">
                                            <h6 class="text-dark mb-1">🎯 Ratings</h6>
                                            <ul class="list-unstyled small mb-2">
                                                <li><strong>Day:</strong> {{ $solunarData['dayRating'] }}</li>
                                                <li><strong>Calc:</strong> {{ $solunarData['calculatedRating'] }}</li>
                                            </ul>
                                            <h6 class="text-dark mb-1">🎣 Activity</h6>
                                            <ul class="list-unstyled small mb-0">
                                                <li><strong>Minor 1:</strong>
                                                    {{ $solunarData['minor1Start'] ?? 'N/A' }}
                                                    – {{ $solunarData['minor1Stop'] ?? 'N/A' }}
                                                </li>
                                                <li><strong>Minor 2:</strong>
                                                    {{ $solunarData['minor2Start'] ?? 'N/A' }}
                                                    – {{ $solunarData['minor2Stop'] ?? 'N/A' }}
                                                </li>
                                                <li><strong>Major 1:</strong>
                                                    {{ $solunarData['major1Start'] ?? 'N/A' }}
                                                    – {{ $solunarData['major1Stop'] ?? 'N/A' }}
                                                </li>
                                                <li><strong>Major 2:</strong>
                                                    {{ $solunarData['major2Start'] ?? 'N/A' }}
                                                    – {{ $solunarData['major2Stop'] ?? 'N/A' }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    {{-- Hourly Activity --}}
                                    <hr class="my-3">
                                    <div class="mt-2">
                                        <h6 class="fw-semibold mb-2">⏰ Hourly Activity Rating</h6>

                                        {{-- Color Legend --}}
                                        <div class="mb-2 d-flex align-items-center flex-wrap small">
                                            <div class="d-flex align-items-center me-3 mb-1">
                                                <div class="rounded-circle bg-success me-2"
                                                     style="width: 12px; height: 12px;"></div>
                                                <span>High (40+)</span>
                                            </div>
                                            <div class="d-flex align-items-center me-3 mb-1">
                                                <div class="rounded-circle bg-warning me-2"
                                                     style="width: 12px; height: 12px;"></div>
                                                <span>Moderate (20–39)</span>
                                            </div>
                                            <div class="d-flex align-items-center me-3 mb-1">
                                                <div class="rounded-circle bg-light border me-2"
                                                     style="width: 12px; height: 12px;"></div>
                                                <span>Low (0–19)</span>
                                            </div>
                                        </div>

                                        {{-- Hourly Rating Blocks --}}
                                        <div class="d-flex flex-wrap">
                                            @foreach($solunarData['hourlyRating'] as $hour => $rating)
                                                @php
                                                    $color = match(true) {
                                                        $rating >= 40 => 'bg-success text-white',
                                                        $rating >= 20 => 'bg-warning text-dark',
                                                        default => 'bg-light text-muted'
                                                    };
                                                @endphp
                                                <div class="text-center border rounded p-1 m-1 {{ $color }}"
                                                     style="width: 45px; font-size: 0.7rem;">
                                                    <div>{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}</div>
                                                    <div>{{ $rating }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @php
                                $themeColor = 'linear-gradient(135deg, #00c6ff 0%, #0072ff 100%)'; // blue gradient
                            @endphp

                            <div class="row mb-4">
                                @if (!empty($currentsData))
                                    <!-- Tide Information -->
                                    <div class="col-md-6 mb-4">
                                        <div class="p-4 rounded-4 shadow-sm text-white text-center h-100 border border-dark"
                                             style="background: {{ $themeColor }};">
                                            <h5 class="fw-bold">🌊 Tide Information</h5>
                                            <small>
                                                <strong>High
                                                    Tide:</strong> {{ !empty($modalData->high_tide_time) && $modalData->high_tide_time !== 'N/A' ? \Carbon\Carbon::parse($modalData->high_tide_time)->format(' g:i a') : 'N/A' }}
                                                ({{ $modalData->high_tide_level ?? 'N/A' }}m)<br>
                                                <strong>Low
                                                    Tide:</strong> {{ !empty($modalData->low_tide_time) && $modalData->low_tide_time !== 'N/A' ? \Carbon\Carbon::parse($modalData->low_tide_time)->format(' g:i a') : 'N/A' }}
                                                ({{ $modalData->low_tide_level ?? 'N/A' }}m)<br>
                                                <strong>Water
                                                    Temperature:</strong> {{ $modalData->water_temperature ?? 'N/A' }}°C
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Current Predictions -->
                                    <div class="col-md-6 mb-4">
                                        <div class="p-4 rounded-4 shadow-sm text-white h-100"
                                             style="background: {{ $themeColor }};">
                                            <h5 class="fw-bold text-center mb-3">🌊 Current Predictions</h5>
                                            <div class="table-responsive">
                                                <table class="table table-borderless table-sm text-white text-center align-middle mb-0">
                                                    <thead>
                                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.4);">
                                                        <th>Time</th>
                                                        <th>Velocity<br><small>(cm/s)</small></th>
                                                        <th>Depth<br><small>(m)</small></th>
                                                        <th>Flood Dir<br><small>(°)</small></th>
                                                        <th>Ebb Dir<br><small>(°)</small></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach ($currentsData as $prediction)
                                                        <tr>
                                                            <td>{{ \Carbon\Carbon::parse($prediction['Time'])->format('M d, Y h:i A') }}</td>
                                                            <td>{{ number_format($prediction['Velocity_Major'], 1) }}</td>
                                                            <td>{{ number_format($prediction['Depth'], 1) }}</td>
                                                            <td>{{ $prediction['meanFloodDir'] }}°</td>
                                                            <td>{{ $prediction['meanEbbDir'] }}°</td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <!-- Only Tide Information Centered -->
                                    <div class="col-md-8 offset-md-2">
                                        <div class="p-2 rounded-4 shadow-sm text-white text-center"
                                             style="background: {{ $themeColor }};">
                                            <h5 class="fw-bold">🌊 Tide Information</h5>
                                            <small>
                                                <strong>High
                                                    Tide:</strong> {{ !empty($modalData->high_tide_time) && $modalData->high_tide_time !== 'N/A' ? \Carbon\Carbon::parse($modalData->high_tide_time)->format(' g:i a') : 'N/A' }}
                                                ({{ $modalData->high_tide_level ?? 'N/A' }}m)<br>
                                                <strong>Low
                                                    Tide:</strong> {{ !empty($modalData->low_tide_time) && $modalData->low_tide_time !== 'N/A' ? \Carbon\Carbon::parse($modalData->low_tide_time)->format(' g:i a') : 'N/A' }}
                                                ({{ $modalData->low_tide_level ?? 'N/A' }}m)<br>
                                                <strong>Water
                                                    Temperature:</strong> {{ $modalData->water_temperature ?? 'N/A' }}°C
                                            </small>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Weather Section -->
                            <div class="container bg-light p-4 rounded-3 shadow-sm text-center border border-dark">
                                <button class="btn btn-primary rounded-pill float-end"
                                        wire:click="toggleTemperatureUnit">
                                    Toggle to {{ $temperatureUnit === 'C' ? '°F' : '°C' }}
                                </button>
                                <h5 class="text-dark fw-bold">🌤️ Weather Data</h5>
                                <small>
                                    <strong>🌡 Max
                                        Temperature:</strong> {{ $this->getTemperature($modalData->max_temp ?? 0) }}<br>
                                    <strong>🌡 Min
                                        Temperature:</strong> {{ $this->getTemperature($modalData->min_temp ?? 0) }}<br>
                                    <strong>🌧 Precipitation:</strong> {{ $modalData->precipitation ?? 'N/A' }} mm<br>
                                    <strong>💨 Wind Speed:</strong> {{ $modalData->wind_speed ?? 'N/A' }} m/s<br>
                                    <strong>🧭 Wind
                                        Direction:</strong> {{ $modalData->weather->wind_direction ?? 'N/A' }}
                                </small>
                            </div>


                        </div>


                        {{--                        @foreach ($stationMoreData as $product => $content)--}}
                        {{--                            <h5 class="mt-4 text-capitalize">{{ str_replace('_', ' ', $product) }}</h5>--}}

                        {{--                            @if (is_array($content) && isset($content[$product]) && is_array($content[$product]))--}}
                        {{--                                @php--}}
                        {{--                                    $rows = $content[$product];--}}
                        {{--                                @endphp--}}

                        {{--                                <div class="table-responsive">--}}
                        {{--                                    <table class="table table-sm table-bordered">--}}
                        {{--                                        <thead>--}}
                        {{--                                        <tr>--}}
                        {{--                                            @foreach (array_keys($rows[0] ?? []) as $key)--}}
                        {{--                                                <th>{{ ucfirst($key) }}</th>--}}
                        {{--                                            @endforeach--}}
                        {{--                                        </tr>--}}
                        {{--                                        </thead>--}}
                        {{--                                        <tbody>--}}
                        {{--                                        @foreach (array_slice($rows, 0, 10) as $row)--}}
                        {{--                                            <tr>--}}
                        {{--                                                @foreach ($row as $cell)--}}
                        {{--                                                    <td>{{ $cell }}</td>--}}
                        {{--                                                @endforeach--}}
                        {{--                                            </tr>--}}
                        {{--                                        @endforeach--}}
                        {{--                                        </tbody>--}}
                        {{--                                    </table>--}}

                        {{--                                    @if (count($rows) > 10)--}}
                        {{--                                        <button class="btn btn-sm btn-link" data-bs-toggle="collapse"--}}
                        {{--                                                data-bs-target="#more-{{ $product }}">Show more--}}
                        {{--                                        </button>--}}
                        {{--                                        <div class="collapse" id="more-{{ $product }}">--}}
                        {{--                                            <table class="table table-sm table-bordered mt-2">--}}
                        {{--                                                <tbody>--}}
                        {{--                                                @foreach (array_slice($rows, 10) as $row)--}}
                        {{--                                                    <tr>--}}
                        {{--                                                        @foreach ($row as $cell)--}}
                        {{--                                                            <td>{{ $cell }}</td>--}}
                        {{--                                                        @endforeach--}}
                        {{--                                                    </tr>--}}
                        {{--                                                @endforeach--}}
                        {{--                                                </tbody>--}}
                        {{--                                            </table>--}}
                        {{--                                        </div>--}}
                        {{--                                    @endif--}}
                        {{--                                </div>--}}
                        {{--                            @elseif(is_array($content))--}}
                        {{--                                <pre>{{ json_encode($content, JSON_PRETTY_PRINT) }}</pre>--}}
                        {{--                            @else--}}
                        {{--                                <p class="text-muted">{{ $content }}</p>--}}
                        {{--                            @endif--}}
                        {{--                        @endforeach--}}

                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark w-100 rounded-pill" wire:click="closeModal">
                                Close
                            </button>
                        </div>
                    @else
                        <!-- Message when no data is found -->
                        <div class="text-center py-5">
                            <h4 class="text-muted">🚫 No Data Available</h4>
                            <p class="text-secondary">We couldn't find any tide or weather data for the selected
                                date.</p>
                            <button class="btn btn-primary rounded-pill" wire:click="closeModal">Close</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    <style>

        /* Make sure the entire calendar spans the full page width */
        .calendar-container {
            width: 100vw; /* Full viewport width */
            max-width: 100vw;
            margin: 0;
            padding: 20px;
            background: #fff;
            border-radius: 0; /* Remove border-radius to make it fully extend */
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Remove Bootstrap's default container padding */
        .container-fluid, .container {
            /* max-width: 100%;
            padding-left: 0;
            padding-right: 0; */
        }

        /* Ensure full-width row */
        .calendar-container .row {
            margin-left: 0;
            margin-right: 0;
        }

        /* Fully expand the accordion */
        .accordion {
            width: 100%;
        }

        /* Expand accordion-body */
        .accordion-body {
            width: 100%;
            padding: 20px;
        }

        /* Ensure calendar grid fills available width */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            background: transparent;
            padding: 10px;
            border-radius: 10px;
            width: 100%;
        }

        /* Ensure each calendar day takes full width */
        .calendar-day {
            background: rgba(255, 255, 255, 0.55);
            padding: 4px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #dee2e6;
            width: 100%;
        }

        /* Remove left/right margin from Bootstrap's default container */
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden; /* Prevent horizontal scroll issues */
        }

        /* Adjust for tablets */
        @media (min-width: 768px) {
            .calendar-container {
                padding: 30px;
            }
        }

        /* Adjust for larger screens */
        @media (min-width: 1024px) {
            .calendar-container {
                padding: 40px;
            }
        }

        .fixed-header {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            z-index: 1000;
            border-radius: 10px;
        }

        .header-content {
            text-align: center;
        }

        .header-spacer {
            height: 120px;
        }

        .full-page-loader {
            position: fixed;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            top: 0;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1050;
        }

        .loader-content {
            text-align: center;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            load_view();

            let stationSelect = $('#stationSelect');
            stationSelect.select2();

            stationSelect.on('change', function () {
                let selectedStation = parseInt($(this).val(), 10);
                if (!isNaN(selectedStation)) {
                    $('#loader').show();
                    Livewire.dispatch('updateStation', {stationId: selectedStation});
                    console.log("Livewire Event Dispatched:", selectedStation);
                }
            });

            Livewire.on('preserve-scroll', () => {
                load_view();
            });

            Livewire.hook('message.processed', () => {
                $('#stationSelect').select2();
                $('#loader').hide();
            });
        });

        function load_view() {
            // const today = new Date();
            // const todayStr = today.toISOString().split('T')[0];
            //
            // const dateCell = document.querySelector(`[data-date="${todayStr}"]`);
            //
            // if (dateCell) {
            //     const collapseEl = dateCell.closest('.accordion-collapse');
            //     const accordionItem = collapseEl.closest('.accordion-item');
            //     const accordionButton = accordionItem.querySelector('.accordion-button');
            //
            //     document.querySelectorAll('.accordion-collapse').forEach(el => el.classList.remove('show'));
            //     document.querySelectorAll('.accordion-button').forEach(btn => {
            //         btn.classList.add('collapsed');
            //         btn.setAttribute('aria-expanded', 'false');
            //     });
            //
            //     if (collapseEl) {
            //         collapseEl.classList.add('show');
            //     }
            //     if (accordionButton) {
            //         accordionButton.classList.remove('collapsed');
            //         accordionButton.setAttribute('aria-expanded', 'true');
            //     }
            // }

            setTimeout(() => {
                const todayElement = document.querySelector('.calendar-day[style*="background-color: lightgreen"]');
                if (todayElement) {
                    todayElement.scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            }, 500);
        }
    </script>
</div>


