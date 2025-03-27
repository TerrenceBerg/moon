<div>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Widget Card -->
    <div class="card p-4 shadow-sm rounded-4 border-0 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-semibold text-primary mb-1">
                    📅 {{ $dayData['gregorian_date'] }}
                </h5>
                <p class="text-muted mb-0 small">Daily summary for this date</p>
            </div>

            <button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#dayDetailsModal">
                More Details
            </button>
        </div>

        <ul class="list-unstyled mb-0">
            <li class="mb-2 d-flex align-items-center">
                <span class="me-2 fs-5">🌕</span>
                <span><strong>Moon Phase:</strong> {{ $dayData['moon_phase'] }}</span>
            </li>

            @if(isset($dayData['solunar_rating']))
                @php
                    $rating = $dayData['solunar_rating'];
                @endphp

                <div class="mt-3 d-flex align-items-center">
        <span class="me-2 text-dark fw-semibold">
            🎯 Solunar Rating:
        </span>
                    <div>
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
                    <span class="ms-2 small text-muted">({{ number_format($rating, 1) }} / 4.0)</span>
                </div>
                <br>
            @endif

            @if ($dayData['all_data'])
                <li class="mb-2 d-flex align-items-center">
                    <span class="me-2 fs-5">🌊</span>
                    <span>
                    <strong>High Tide:</strong> {{ $dayData['all_data']['high_tide_time'] }}
                    ({{ $dayData['all_data']['high_tide_level'] }}m)
                </span>
                </li>
                <li class="d-flex align-items-center">
                    <span class="me-2 fs-5">🏖️</span>
                    <span>
                    <strong>Low Tide:</strong> {{ $dayData['all_data']['low_tide_time'] }}
                    ({{ $dayData['all_data']['low_tide_level'] }}m)
                </span>
                </li>
            @else
                <li class="text-muted fst-italic">No tide data available.</li>
            @endif
        </ul>
    </div>
    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="dayDetailsModal" tabindex="-1" aria-labelledby="dayDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4">
                <div class="modal-header bg-gradient text-white"
                     style="background: linear-gradient(to right, #1e3c72, #2a5298);">
                    <h5 class="modal-title text-white">🌙 Astronomical Data for </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <?php $solunarData=$dayData['solunar_data']; ?>
                    @if($solunarData)
                        <div class="container card rounded border-dark mb-3 p-3">
                            <h5 class="fw-bold mb-3 border-bottom pb-2">🌤️ Solunar Summary</h5>
                            <div class="row text-sm text-muted">
                                <!-- Sun Info -->
                                <div class="col-md-4 mb-2">
                                    <h6 class="text-dark mb-1">🌞 Sun</h6>
                                    <ul class="list-unstyled small mb-0">
                                        <li><strong>Rise:</strong> {{ $solunarData['sunRise'] }}</li>
                                        <li><strong>Transit:</strong> {{ $solunarData['sunTransit'] }}</li>
                                        <li><strong>Set:</strong> {{ $solunarData['sunSet'] }}</li>
                                    </ul>
                                </div>

                                <!-- Moon Info -->
                                <div class="col-md-4 mb-2">
                                    <h6 class="text-dark mb-1">🌙 Moon</h6>
                                    <ul class="list-unstyled small mb-0">
                                        <li><strong>Rise:</strong> {{ $solunarData['moonRise'] }}</li>
                                        <li><strong>Transit:</strong> {{ $solunarData['moonTransit'] }}</li>
                                        <li><strong>Set:</strong> {{ $solunarData['moonSet'] }}</li>
                                        <li><strong>Phase:</strong> {{ $solunarData['moonPhase'] }}</li>
                                        <li><strong>Illumination:</strong> {{ round($solunarData['moonIllumination'] * 100) }}%</li>
                                    </ul>
                                </div>

                                <!-- Ratings -->
                                <div class="col-md-4 mb-2">
                                    <h6 class="text-dark mb-1">🎯 Ratings</h6>
                                    <ul class="list-unstyled small mb-2">
                                        <li><strong>Day:</strong> {{ $solunarData['dayRating'] }}</li>
                                        <li><strong>Calc:</strong> {{ $solunarData['calculatedRating'] }}</li>
                                    </ul>

                                    <h6 class="text-dark mb-1">🎣 Activity</h6>
                                    <ul class="list-unstyled small mb-0">
                                        <li><strong>Minor 1:</strong> {{ $solunarData['minor1Start'] }} – {{ $solunarData['minor1Stop'] }}</li>
                                        <li><strong>Minor 2:</strong> {{ $solunarData['minor2Start'] }} – {{ $solunarData['minor2Stop'] }}</li>
                                        <li><strong>Major 1:</strong> {{ $solunarData['major1Start'] }} – {{ $solunarData['major1Stop'] }}</li>
                                        <li><strong>Major 2:</strong> {{ $solunarData['major2Start'] }} – {{ $solunarData['major2Stop'] }}</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Hourly Ratings -->
                            <hr class="my-3">
                            <div class="mt-2">
                                <h6 class="fw-semibold mb-2">⏰ Hourly Activity Rating</h6>

                                <!-- Color Legend -->
                                <div class="mb-2 d-flex align-items-center flex-wrap small">
                                    <div class="d-flex align-items-center me-3 mb-1">
                                        <div class="rounded-circle bg-success me-2" style="width: 12px; height: 12px;"></div>
                                        <span>High (40+)</span>
                                    </div>
                                    <div class="d-flex align-items-center me-3 mb-1">
                                        <div class="rounded-circle bg-warning me-2" style="width: 12px; height: 12px;"></div>
                                        <span>Moderate (20–39)</span>
                                    </div>
                                    <div class="d-flex align-items-center me-3 mb-1">
                                        <div class="rounded-circle bg-light border me-2" style="width: 12px; height: 12px;"></div>
                                        <span>Low (0–19)</span>
                                    </div>
                                </div>

                                <!-- Hourly Rating Blocks -->
                                <div class="d-flex flex-wrap">
                                    @foreach($solunarData['hourlyRating'] as $hour => $rating)
                                        @php
                                            $color = match(true) {
                                                $rating >= 40 => 'bg-success text-white',
                                                $rating >= 20 => 'bg-warning text-dark',
                                                default => 'bg-light text-muted'
                                            };
                                        @endphp
                                        <div class="text-center border rounded p-1 m-1 {{ $color }}" style="width: 45px; font-size: 0.7rem;">
                                            <div>{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}</div>
                                            <div>{{ $rating }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Additional Environmental Data --}}
                    @php
                        $themeColor = 'linear-gradient(135deg, #00c6ff 0%, #0072ff 100%)';
                    @endphp

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="p-4 rounded-4 shadow-sm text-white text-center h-100 border border-dark"
                                 style="background: {{ $themeColor }};">
                                <h5 class="fw-bold mb-3">🌊 Tide Information</h5>
                                <p><strong>High Tide:</strong> {{ $dayData['all_data']['high_tide_time'] ?? 'N/A' }} </p>
                                <p><strong>Low Tide:</strong> {{ $dayData['all_data']['low_tide_time'] ?? 'N/A' }} </p>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="p-4 rounded-4 shadow-sm text-white text-center h-100 border border-dark"
                                 style="background: {{ $themeColor }};">
                                <h5 class="fw-bold mb-3">🌡️ Temperature & Weather</h5>
                                <p><strong>Max Temp:</strong> {{ $dayData['all_data']['max_temp'] ?? 'N/A' }} °C</p>
                                <p><strong>Min Temp:</strong> {{ $dayData['all_data']['min_temp'] ?? 'N/A' }} °C</p>
                                <p><strong>Precipitation:</strong> {{ $dayData['all_data']['precipitation_mm'] ?? 'N/A' }} mm</p>
                                <p><strong>Weather:</strong> {{ $dayData['all_data']['weather_description'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
