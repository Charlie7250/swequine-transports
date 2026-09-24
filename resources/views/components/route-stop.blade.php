@props(['type', 'job' => null, 'depot' => null, 'position' => null])

@if ($type === 'depot')
    <div class="route-stop route-stop--depot">
        <span class="route-stop-marker"><x-prototype-icon name="depot" :size="18" /></span>
        <div class="route-stop-copy">
            <strong>{{ $position }} at depot</strong>
            <span>{{ $depot }}</span>
        </div>
    </div>
@else
    <div class="route-stop route-stop--job" id="prototype-job-{{ $job['id'] }}">
        <span class="route-stop-sequence">{{ $job['sequence'] }}</span>
        <div class="route-job-main">
            <div class="route-job-title">
                <strong>Collect {{ $job['horse_count'] }} {{ \Illuminate\Support\Str::plural('horse', $job['horse_count']) }}</strong>
                <span>{{ $job['customer'] }}</span>
                <small>Job #{{ $job['id'] }}</small>
            </div>
            <div class="route-job-journey">
                <span><strong>Collection:</strong> {{ $job['pickup'] ?? 'not recorded' }}</span>
                <x-prototype-icon name="arrow" :size="15" />
                <span><strong>Drop-off:</strong> {{ $job['dropoff'] ?? 'not recorded' }}</span>
            </div>
            <div class="route-job-outcome">
                <x-job-status :status="$job['status']" />
                <span class="route-job-value">{{ $job['value'] ?? 'Not priced' }}</span>
                <a class="route-job-action" href="{{ $job['action_url'] ?? '#prototype-job-'.$job['id'] }}" aria-label="View job {{ $job['id'] }} for {{ $job['customer'] }}">
                    View job
                    <x-prototype-icon name="arrow" :size="14" />
                </a>
            </div>
        </div>
    </div>
@endif
