@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    <header class="dashboard-hero">
        <div>
            <h1>Dashboard</h1>
            <p class="dashboard-date-meta">{{ $dashboard['display_date'] }}</p>
        </div>
        <a class="prototype-button prototype-button--primary" href="{{ route('quotes.create') }}">
            <x-prototype-icon name="plus" :size="20" />
            New quote
        </a>
    </header>

    <section class="dashboard-summary-grid" aria-label="Operational summary">
        @foreach ($dashboard['summary'] as $item)
            <x-dashboard-summary-card :item="$item" />
        @endforeach
    </section>

    <section class="dashboard-section">
        <header class="dashboard-section-heading">
            <div>
                <h2>Upcoming transport days</h2>
            </div>
            <div class="dashboard-section-tools">
                <x-dashboard-date-controls :range="$dashboard['date_range']" />
                <a class="dashboard-section-link" href="{{ route('transport-days.index') }}">View all transport days</a>
            </div>
        </header>

        <div class="transport-day-list">
            @forelse ($dashboard['days'] as $day)
                <x-transport-day-card :day="$day" />
            @empty
                <div class="transport-day-empty dashboard-empty-window">
                    <span class="transport-day-empty-icon"><x-prototype-icon name="calendar" :size="24" /></span>
                    <div>
                        <strong>No transport days in this week</strong>
                        <p>Create a day before assigning work to this period.</p>
                    </div>
                    <a class="prototype-button prototype-button--quiet" href="{{ route('transport-days.create') }}">Create transport day</a>
                </div>
            @endforelse
        </div>
    </section>

    <section class="dashboard-section dashboard-section--unassigned">
        <header class="dashboard-section-heading">
            <div>
                <h2>Unassigned work</h2>
            </div>
            <span class="dashboard-count">{{ $dashboard['unassigned_jobs']->count() }}</span>
        </header>

        @if ($dashboard['unassigned_jobs']->isNotEmpty())
            <div class="unassigned-list">
                @foreach ($dashboard['unassigned_jobs'] as $job)
                <article class="unassigned-job">
                    <span class="unassigned-job-icon"><x-prototype-icon name="document" :size="22" /></span>
                    <div class="unassigned-job-copy">
                        <strong>Collect {{ $job['horse_count'] }} {{ \Illuminate\Support\Str::plural('horse', $job['horse_count']) }} for {{ $job['customer'] }}</strong>
                        <span>Job #{{ $job['id'] }}. Collection: {{ $job['pickup'] ?? 'not recorded' }}. Drop-off: {{ $job['dropoff'] ?? 'not recorded' }}.</span>
                    </div>
                    <div class="unassigned-job-meta">
                        <span>{{ $job['horse_count'] }} {{ \Illuminate\Support\Str::plural('horse', $job['horse_count']) }}</span>
                        <x-job-status :status="$job['status']" />
                    </div>
                    <div class="unassigned-job-actions">
                        @if ($job['action_url'])
                            <a class="prototype-button prototype-button--quiet" href="{{ $job['action_url'] }}">View job</a>
                        @endif
                        <a class="prototype-button prototype-button--quiet" href="{{ route('transport-days.index') }}">Assign to a day</a>
                    </div>
                </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
