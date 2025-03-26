<div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <div class="fixed-header shadow-sm">
        <div class="header-content container">
            <h4 class="text-primary mb-1">📍 Location: <strong>{{$location['city']}}</strong></h4>
            <h5 class="text-muted mb-2">🌍 Nearest NOAA Station: <strong>{{$selectedStation->name}}</strong></h5>
            <div class="">
                <label for="stationSelect" class="form-label fw-bold">Select Station:</label><br>
                <select wire:model="selectedStation" id="stationSelect" class="form-control">
                    @foreach ($stations as $station)
                        <option value="{{ $station->id }}" @if(isset($selectedStation) && $selectedStation->id==$station->id) selected @endif>{{ $station->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="header-spacer mt-5"></div>

    <div wire:loading.delay class="full-page-loader">
        <div class="loader-content">
            <div class="spinner-border text-light" role="status"></div>
            <p class="mt-2 text-white fw-bold">Fetching Data...</p>
        </div>
    </div>

    <div wire:loading.remove>
        <div class="calendar-container">
            <h1 class="text-center text-primary">📅 13-Month Calendar</h1>
            <div class="accordion" id="yearAccordion">
                @foreach ($calendarData as $year => $data)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-{{ $year }}">
                            <button class="accordion-button " type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse-{{ $year }}"
                                    aria-controls="collapse-{{ $year }}">
                                Year {{ $year }}
                            </button>
                        </h2>
                        <div id="collapse-{{ $year }}"
                             class="accordion-collapse collapse "
                             aria-labelledby="heading-{{ $year }}" data-bs-parent="#yearAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    @foreach ($data['months'] as $index=>$month)
                                        <div class="calendar-month col-lg-12 col-md-12 col-sm-12 mt-4" id="month-{{ $month['name'] }}">
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
                                                    <div class="calendar-day"
                                                         id="day-{{ $day['date'] }}"
                                                         style="{{ $day['is_today'] ? 'background-color: lightgreen; color: black; border: 2px solid green;' : '' }}">

                                                        <!-- Desktop View (Hides on Mobile) -->
                                                        <div class="d-none d-md-block">
                                                            <span class="gregorian-date" data-date="{{ \Carbon\Carbon::parse($day['gregorian_date'])->format('Y-m-d') }}">{{ $day['gregorian_date'] }}</span>
                                                            <span class="date-info">{{ $day['julian_day'] }}</span>

                                                            <span class="date-info">{{ $day['moon_phase'] }}</span>

                                                            @php $tide = $day['tide_data']; @endphp
                                                            @if ($tide)
                                                                <span class="date-info">🌊 High: {{ $tide['high_tide_time'] }} ({{ $tide['high_tide_level'] }}m)</span>
                                                                <span class="date-info">🌊 Low: {{ $tide['low_tide_time'] }} ({{ $tide['low_tide_level'] }}m)</span>
                                                            @endif
                                                            <button class="btn btn-sm btn-light mt-2"
                                                                    wire:click="loadMoreData('{{ $day['date'] }}')">
                                                                <i class="bi bi-info-circle-fill text-primary"></i>
                                                            </button>
                                                        </div>

                                                        <!-- Mobile View (Hides on Desktop) -->
                                                        <div class="d-block d-md-none text-center">
                                                            <a style="cursor: pointer; font-size: 10px; font-weight: bold" href="" class="text-dark"
                                                               wire:click.prevent="loadMoreData('{{ $day['date'] }}')">
                                                                {{++$i}}
                                                            </a>
                                                        </div>

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
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog"
             style="background: rgba(0, 0, 0, 0.5);">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down" role="document">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    @if ($modalData)
                    <div class="modal-header bg-gradient text-white"
                         style="background: linear-gradient(to right, #1e3c72, #2a5298);">
                        <h5 class="modal-title text-dark">🌙 Astronomical Data for {{ $selectedDate }}</h5>
                        <button type="button" class="btn-close" wire:click.prevent="closeModal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <h5 class="text-primary">{{ $modalData->moon_phase ?? 'N/A' }}</h5>
                        </div>
                        <div class="row text-center mb-4">
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded-3 shadow-sm">
                                    <h6 class="text-warning">☀️ Sunrise</h6>
                                    <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($modalData->sunrise)->format('h:i:s a') ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded-3 shadow-sm">
                                    <h6 class="text-danger">🌅 Sunset</h6>
                                    <p class="mb-0 fw-bold">{{\Carbon\Carbon::parse($modalData->sunset)->format('h:i:s a')?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        @php
                            $themeColor = 'linear-gradient(135deg, #00c6ff 0%, #0072ff 100%)'; // blue gradient
                        @endphp

                        <div class="row mb-4">
                            @if (!empty($currentsData))
                                <!-- Tide Information -->
                                <div class="col-md-6 mb-4">
                                    <div class="p-4 rounded-4 shadow-sm text-white text-center h-100"
                                         style="background: {{ $themeColor }};">
                                        <h5 class="fw-bold mb-3">🌊 Tide Information</h5>
                                        <p class="mb-2"><strong>High Tide:</strong> {{ $modalData->high_tide_time ?? 'N/A' }}
                                            ({{ $modalData->high_tide_level ?? 'N/A' }}m)</p>
                                        <p class="mb-2"><strong>Low Tide:</strong> {{ $modalData->low_tide_time ?? 'N/A' }}
                                            ({{ $modalData->low_tide_level ?? 'N/A' }}m)</p>
                                        <p class="mb-0"><strong>Water Temperature:</strong> {{ $modalData->water_temperature ?? 'N/A' }}°C</p>
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
                                    <div class="p-4 rounded-4 shadow-sm text-white text-center mb-4"
                                         style="background: {{ $themeColor }};">
                                        <h5 class="fw-bold mb-3">🌊 Tide Information</h5>
                                        <p class="mb-2"><strong>High Tide:</strong> {{ $modalData->high_tide_time ?? 'N/A' }}
                                            ({{ $modalData->high_tide_level ?? 'N/A' }}m)</p>
                                        <p class="mb-2"><strong>Low Tide:</strong> {{ $modalData->low_tide_time ?? 'N/A' }}
                                            ({{ $modalData->low_tide_level ?? 'N/A' }}m)</p>
                                        <p class="mb-0"><strong>Water Temperature:</strong> {{ $modalData->water_temperature ?? 'N/A' }}°C</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-outline-primary rounded-pill" wire:click="toggleTemperatureUnit">
                                Toggle to {{ $temperatureUnit === 'C' ? '°F' : '°C' }}
                            </button>
                        </div>

                        <!-- Weather Section -->
                        <div class="bg-light p-4 rounded-3 shadow-sm text-center">
                            <h5 class="text-primary fw-bold">🌤️ Weather Data</h5>
                            <p><strong>🌡 Max Temperature:</strong> {{ $this->getTemperature($modalData->max_temp ?? 0) }}</p>
                            <p><strong>🌡 Min Temperature:</strong> {{ $this->getTemperature($modalData->min_temp ?? 0) }}</p>
                            <p><strong>🌧 Precipitation:</strong> {{ $modalData->precipitation ?? 'N/A' }} mm</p>
                            <p><strong>💨 Wind Speed:</strong> {{ $modalData->wind_speed ?? 'N/A' }} m/s</p>
                            <p><strong>🧭 Wind Direction:</strong> {{ $modalData->weather->wind_direction ?? 'N/A' }}</p>
                        </div>

                    </div>
                        @foreach ($stationMoreData as $product => $content)
                            <h5 class="mt-4 text-capitalize">{{ str_replace('_', ' ', $product) }}</h5>

                            @if (is_array($content) && isset($content[$product]) && is_array($content[$product]))
                                @php
                                    $rows = $content[$product];
                                @endphp

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                        <tr>
                                            @foreach (array_keys($rows[0] ?? []) as $key)
                                                <th>{{ ucfirst($key) }}</th>
                                            @endforeach
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach (array_slice($rows, 0, 10) as $row)
                                            <tr>
                                                @foreach ($row as $cell)
                                                    <td>{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>

                                    @if (count($rows) > 10)
                                        <button class="btn btn-sm btn-link" data-bs-toggle="collapse" data-bs-target="#more-{{ $product }}">Show more</button>
                                        <div class="collapse" id="more-{{ $product }}">
                                            <table class="table table-sm table-bordered mt-2">
                                                <tbody>
                                                @foreach (array_slice($rows, 10) as $row)
                                                    <tr>
                                                        @foreach ($row as $cell)
                                                            <td>{{ $cell }}</td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @elseif(is_array($content))
                                <pre>{{ json_encode($content, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <p class="text-muted">{{ $content }}</p>
                            @endif
                        @endforeach

                    <div class="modal-footer">
                        <button type="button" class="btn btn-dark w-100 rounded-pill" wire:click="closeModal">Close</button>
                    </div>
                    @else
                        <!-- Message when no data is found -->
                        <div class="text-center py-5">
                            <h4 class="text-muted">🚫 No Data Available</h4>
                            <p class="text-secondary">We couldn't find any tide or weather data for the selected date.</p>
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
            max-width: 100%;
            padding-left: 0;
            padding-right: 0;
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
            background: #e9ecef;
            padding: 10px;
            border-radius: 10px;
            width: 100%;
        }

        /* Ensure each calendar day takes full width */
        .calendar-day {
            background: white;
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                    Livewire.dispatch('updateStation', { stationId: selectedStation });
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
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];

            const dateCell = document.querySelector(`[data-date="${todayStr}"]`);

            if (dateCell) {
                const collapseEl = dateCell.closest('.accordion-collapse');
                const accordionItem = collapseEl.closest('.accordion-item');
                const accordionButton = accordionItem.querySelector('.accordion-button');

                document.querySelectorAll('.accordion-collapse').forEach(el => el.classList.remove('show'));
                document.querySelectorAll('.accordion-button').forEach(btn => {
                    btn.classList.add('collapsed');
                    btn.setAttribute('aria-expanded', 'false');
                });

                if (collapseEl) {
                    collapseEl.classList.add('show');
                }
                if (accordionButton) {
                    accordionButton.classList.remove('collapsed');
                    accordionButton.setAttribute('aria-expanded', 'true');
                }
            }

            setTimeout(() => {
                const todayElement = document.querySelector('.calendar-day[style*="background-color: lightgreen"]');
                if (todayElement) {
                    todayElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 500);
        }
    </script>
</div>


