@props(['range' => '20 - 26 Sep 2026'])

<div class="dashboard-date-controls" aria-label="Schedule date controls">
    <div class="dashboard-date-stepper" role="group" aria-label="Displayed date range">
        <button class="dashboard-date-arrow dashboard-date-arrow--previous" type="button" aria-label="Previous date range">
            <x-prototype-icon name="arrow" :size="15" />
        </button>
        <span>{{ $range }}</span>
        <button class="dashboard-date-arrow" type="button" aria-label="Next date range">
            <x-prototype-icon name="arrow" :size="15" />
        </button>
    </div>
    <button class="dashboard-date-today" type="button">Today</button>
    <div class="dashboard-date-views" role="group" aria-label="Schedule period">
        <button type="button" aria-pressed="false">Day</button>
        <button type="button" aria-pressed="true">Week</button>
        <button type="button" aria-pressed="false">Month</button>
    </div>
</div>
