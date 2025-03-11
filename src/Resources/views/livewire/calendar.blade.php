<div>
    <!-- Station Selector -->
    <div class="mb-3">
        <label for="stationSelect" class="form-label">Select Station:</label>
        <select wire:model="selectedStation" id="stationSelect" class="form-control">
            @foreach ($stations as $station)
                <option value="{{ $station->id }}">{{ $station->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- Full Page Loader -->
    <div wire:loading.delay class="full-page-loader">
        <div class="loader-content">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-white">Fetching Data...</p>
        </div>
    </div>

    <!-- Calendar Content -->
    <div wire:loading.remove>
        <div class="calendar-container">
            <h1 class="text-center text-primary">13-Month Calendar</h1>

            <div class="accordion" id="yearAccordion">
                @foreach ($calendarData as $year => $data)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-{{ $year }}">
                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}"
                                    type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse-{{ $year }}"
                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                    aria-controls="collapse-{{ $year }}">
                                Year {{ $year }}
                            </button>
                        </h2>
                        <div id="collapse-{{ $year }}"
                             class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                             aria-labelledby="heading-{{ $year }}" data-bs-parent="#yearAccordion">
                            <div class="accordion-body">

                                <h6 class="text-center">
                                    Solar Events for {{ $year }}
                                    for {{ $stations->firstWhere('station_id', $selectedStation)?->name }}
                                </h6>

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

                                                @foreach($month['days'] as $day)
                                                    <div class="calendar-day">
                                                        <span class="gregorian-date">{{ $day['gregorian_date'] }}</span>
                                                        <span class="date-info">{{ $day['julian_day'] }}</span>
                                                        <span class="date-info">{{ $day['moon_phase'] }}</span>

                                                        @php
                                                            $tide = $day['tide_data'];
                                                        @endphp

                                                        @if ($tide)
                                                            <span class="date-info">🌊 High: {{ $tide['high_tide_time'] }} ({{ $tide['high_tide_level'] }}m)</span>
                                                            <span class="date-info">🌊 Low: {{ $tide['low_tide_time'] }} ({{ $tide['low_tide_level'] }}m)</span>
                                                        @else
                                                            <span class="date-info">Tide data unavailable</span>
                                                        @endif

                                                        <button class="btn btn-sm btn-dark text-white mt-2"
                                                                wire:click="loadMoreData('{{ $day['date'] }}')">
                                                            More
                                                        </button>
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

    <!-- Full Page Loader for Modal -->
    <div wire:loading.delay wire:target="loadMoreData" class="full-page-loader">
        <div class="loader-content">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-white">Fetching Detailed Data...</p>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade @if($showModal) show d-block @endif" tabindex="-1" role="dialog"
         style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-gradient text-white"
                     style="background: linear-gradient(to right, #1e3c72, #2a5298);">
                    <h5 class="modal-title text-dark">🌙 Astronomical Data for {{ $selectedDate }}</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>

                <div class="modal-body p-4">
                    @if ($modalData && $modalData->moon_phase)
                        <div class="text-center mb-4">
                            <h5 class="text-primary">{{ $modalData->moon_phase }}</h5>
                        </div>
                    @endif

                    <div class="row text-center mb-4">
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded-3 shadow-sm">
                                <h6 class="text-warning">☀️ Sunrise</h6>
                                <p class="mb-0 fw-bold">{{ $modalData->sunrise ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded-3 shadow-sm">
                                <h6 class="text-danger">🌅 Sunset</h6>
                                <p class="mb-0 fw-bold">{{ $modalData->sunset ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-info p-4 rounded-3 shadow-sm text-white text-center mb-4">
                        <h5 class="fw-bold">🌊 Tide Information</h5>
                        <p><strong>High Tide:</strong> {{ $modalData->high_tide_time ?? 'N/A' }}
                            ({{ $modalData->high_tide_level ?? 'N/A' }}m)</p>
                        <p><strong>Low Tide:</strong> {{ $modalData->low_tide_time ?? 'N/A' }}
                            ({{ $modalData->low_tide_level ?? 'N/A' }}m)</p>
                        <p><strong>Water Temperature:</strong> {{ $modalData->water_temperature ?? 'N/A' }}°C</p>
                    </div>

                    <!-- Wind & Weather Information -->
                    <div class="bg-light p-4 rounded-3 shadow-sm text-center">
                        <h5 class="text-primary fw-bold">💨 Wind & Weather</h5>
                        <p><strong>Wind Speed:</strong> {{ $modalData->wind_speed ?? 'N/A' }} m/s</p>
                        <p><strong>Wind Direction:</strong> {{ $modalData->wind_direction ?? 'N/A' }}</p>
                        <p><strong>Temperature:</strong> {{ $modalData->temperature ?? 'N/A' }}°C</p>
                    </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-dark w-100 rounded-pill" wire:click="closeModal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
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

    @media (max-width: 768px) {
        .modal-dialog {
            max-width: 95%;
        }

        .modal-content {
            font-size: 14px;
        }
    }
</style>
</div>
