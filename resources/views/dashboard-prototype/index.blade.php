<x-operator-shell title="Dashboard prototype | South West Equine Services" :greeting-name="$greetingName">
    <header class="dashboard-hero">
        <div>
            <h1>Dashboard</h1>
            <p class="dashboard-date-meta">Sunday 20 September 2026</p>
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
                <x-dashboard-date-controls />
                <a class="dashboard-section-link" href="{{ route('transport-days.index') }}">View all transport days</a>
            </div>
        </header>

        <div class="transport-day-list">
            @foreach ($dashboard['days'] as $day)
                <x-transport-day-card :day="$day" />
            @endforeach
        </div>
    </section>

    <section class="dashboard-section dashboard-section--unassigned">
        <header class="dashboard-section-heading">
            <div>
                <h2>Unassigned work</h2>
            </div>
            <span class="dashboard-count">{{ count($dashboard['unassigned_jobs']) }}</span>
        </header>

        @if (count($dashboard['unassigned_jobs']) > 0)
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
                    <a class="prototype-button prototype-button--quiet" href="{{ route('transport-days.index') }}">Assign to a day</a>
                </article>
                @endforeach
            </div>
        @endif
    </section>

    <p class="prototype-caption">Isolated dashboard prototype using fixture data shaped like the current transport domain.</p>
</x-operator-shell>
