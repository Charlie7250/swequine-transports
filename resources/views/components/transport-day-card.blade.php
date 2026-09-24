@props(['day'])

@php($horseCount = $day['horse_count'] ?? collect($day['jobs'])->sum('horse_count'))

<details class="transport-day-card" @if ($day['is_open']) open @endif>
    <summary class="transport-day-summary">
        <span class="transport-day-chevron"><x-prototype-icon name="arrow" :size="18" /></span>
        <span class="transport-day-title">
            <strong>{{ $day['date'] }}</strong>
            @if ($day['name'])
                <span>{{ $day['name'] }}</span>
            @endif
        </span>
        <span class="transport-day-facts">
            <span class="transport-day-fact transport-day-fact--jobs">{{ $day['job_count'] }} {{ \Illuminate\Support\Str::plural('job', $day['job_count']) }}</span>
            <span class="transport-day-fact transport-day-fact--horses">{{ $horseCount }} {{ \Illuminate\Support\Str::plural('horse', $horseCount) }}</span>
            @if ($day['miles'] !== null)
                <span class="transport-day-fact transport-day-fact--miles">{{ $day['miles'] }} quoted miles</span>
            @endif
            @if ($day['duration'] !== null)
                <span class="transport-day-fact transport-day-fact--duration">{{ $day['duration'] }}</span>
            @endif
        </span>
    </summary>

    <div class="transport-day-content">
        @if ($day['jobs'])
            <div class="route-timeline">
                <x-route-stop type="depot" position="Start" :depot="$day['depot']" />
                @foreach ($day['jobs'] as $job)
                    <x-route-stop type="job" :job="$job" />
                @endforeach
                <x-route-stop type="depot" position="Finish" :depot="$day['depot']" />
            </div>
            <div class="transport-day-actions">
                <a class="prototype-button prototype-button--quiet" href="{{ $day['manage_url'] ?? route('transport-days.index') }}">Manage transport day</a>
            </div>
        @else
            <div class="transport-day-empty">
                <span>No jobs assigned yet - available for new work.</span>
                <a class="prototype-button prototype-button--quiet" href="{{ $day['manage_url'] ?? route('transport-days.index') }}">Manage day</a>
            </div>
        @endif
    </div>
</details>
