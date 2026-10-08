@use('App\Models\ContainerInventory')

@php
    $full = $containers[ContainerInventory::AT_STATION_FULL]->quantity ?? 0;
    $empty = $containers[ContainerInventory::AT_STATION_EMPTY]->quantity ?? 0;
    $atStation = $full + $empty;
    $ratio = $atStation > 0 ? $full / $atStation : 0.0;

    // the jug interior runs from y=22 to y=132, so water is measured up from the floor
    $floor = 132;
    $maxWater = 120;
    $waterHeight = max(4, min($maxWater, round($ratio * $maxWater)));
    $waterTop = $floor - $waterHeight;

    $jug = 'M14 22 h72 v96 a14 14 0 0 1 -14 14 h-44 a14 14 0 0 1 -14 -14 z';

    // one sine period spans 100 units, which is the distance the CSS slides the wave
    $wave = 'M -150 0 q 25 -3.5 50 0 t 50 0 t 50 0 t 50 0 t 50 0 t 50 0 V 200 H -150 Z';
@endphp
<div class="row g-4 align-items-center" data-container-visual>
    <div class="col-md-4 text-center">
        <svg viewBox="0 0 100 150" width="150" height="225" role="img" aria-label="Container is {{ round($ratio * 100) }} percent full">
            <defs>
                <clipPath id="containerClip">
                    <path d="{{ $jug }}" />
                </clipPath>
                <clipPath id="waterBody">
                    <rect x="-150" y="0" width="400" height="{{ $waterHeight }}" />
                </clipPath>
            </defs>
            <path d="{{ $jug }}" fill="#eef4ff" stroke="#3d4ca6" stroke-width="4" />
            <g clip-path="url(#containerClip)">
                <g data-water-rect transform="translate(0, {{ $waterTop }})">
                    <g class="aq-water">
                        <g clip-path="url(#waterBody)">
                            <rect x="-150" y="0" width="400" height="200" fill="#659cff" />
                            <path class="aq-glint" fill="#9bbbff" d="M -150 24 h 56 v 6 h -56 Z" />
                        </g>
                        <g transform="translate(100,0)">
                            <path class="aq-wave" fill="#4f8cf0" d="{{ $wave }}" />
                        </g>
                        <path class="aq-wave" fill="#4f8cf0" d="{{ $wave }}" />
                    </g>
                </g>
            </g>
            <path d="{{ $jug }}" fill="none" stroke="#3d4ca6" stroke-width="4" />
            <rect x="26" y="8" width="48" height="14" rx="4" fill="#9bbbff" stroke="#3d4ca6" stroke-width="4" />
            <text x="50" y="82" text-anchor="middle" font-family="Silkscreen, monospace" font-size="20" fill="#3d4ca6" data-fill-percent>{{ round($ratio * 100) }}%</text>
        </svg>
    </div>
    <div class="col-md-8">
        <div class="total-box mb-3">
            <span data-fill-summary>{{ round($ratio * 100, 1) }}% full at station</span>
            <span class="d-block small text-muted" data-fill-detail>{{ $full }} full / {{ $empty }} empty of {{ $atStation }} on hand</span>
        </div>
        <div class="row g-2">
            @foreach ($containers as $status => $container)
                <div class="col-6 col-md-4">
                    <div class="px-card p-2 text-center h-100">
                        <div class="small text-capitalize text-muted">{{ str_replace('_', ' ', $status) }}</div>
                        <div class="h4 mb-0" data-count="{{ $status }}">{{ $container->quantity }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>