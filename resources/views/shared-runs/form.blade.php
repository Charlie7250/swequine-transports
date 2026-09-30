@extends('layouts.app', ['title' => $sharedRun->exists ? 'Shared run builder' : 'New shared run'])

@php
    $availableDraftRevisionsById = $availableDraftRevisions->keyBy('id');
    $routeLegTitles = [
        'depot_to_pickup' => 'Depot to pickup',
        'pickup_to_dropoff' => 'Pickup to drop-off',
        'dropoff_to_depot' => 'Drop-off to depot',
    ];
    $defaultAllocationLegs = [
        ['label' => 'depot_to_pickup', 'full_miles' => null, 'split_miles' => null, 'split_divisor' => null, 'reason' => null],
        ['label' => 'pickup_to_dropoff', 'full_miles' => null, 'split_miles' => null, 'split_divisor' => null, 'reason' => null],
        ['label' => 'dropoff_to_depot', 'full_miles' => null, 'split_miles' => null, 'split_divisor' => null, 'reason' => null],
    ];
    $allocationRows = collect(old('allocations', $allocationRows))
        ->map(function ($allocationRow) use ($defaultAllocationLegs) {
            $oldLegs = collect($allocationRow['legs'] ?? []);

            return [
                'job_revision_id' => $allocationRow['job_revision_id'] ?? null,
                'legs' => collect($defaultAllocationLegs)
                    ->map(function (array $defaultLeg, int $index) use ($oldLegs): array {
                        $oldLeg = $oldLegs->get($index, []);

                        return array_merge($defaultLeg, is_array($oldLeg) ? $oldLeg : []);
                    })
                    ->all(),
            ];
        })
        ->all();
    $allocationSummaries = $sharedRun->exists
        ? $sharedRun->allocations->keyBy('job_revision_id')
        : collect();
    $isCreateScreen = ! $sharedRun->exists;
    $hasAvailableDraftRevisions = $availableDraftRevisions->isNotEmpty();
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Shared loads</span>
            <h1 class="page-title">{{ $sharedRun->exists ? ($sharedRun->name ?: 'Shared run builder') : 'Build a shared run' }}</h1>
            <p class="page-copy">
                Link draft quote revisions into one parent shared run, then record which miles stay fully charged and which miles are genuinely split for each customer allocation.
            </p>
        </section>

        @if (session('status'))
            <section class="status-banner">{{ session('status') }}</section>
        @endif

        @if ($errors->any())
            <section class="error-banner">
                <ul class="bullet-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($sharedRun->exists)
            <section class="card-grid">
                <article class="metric">
                    <div class="metric-label">Run date</div>
                    <div class="metric-value">{{ $sharedRun->run_date?->format('j M Y') ?? 'Pending' }}</div>
                    <div class="metric-copy">Parent shared run record</div>
                </article>
                <article class="metric">
                    <div class="metric-label">Depot postcode</div>
                    <div class="metric-value">{{ $sharedRun->depot_postcode ?? 'Pending' }}</div>
                    <div class="metric-copy">Derived from the linked quote route legs</div>
                </article>
                <article class="metric">
                    <div class="metric-label">Customer allocations</div>
                    <div class="metric-value">{{ $sharedRun->allocations->count() }}</div>
                    <div class="metric-copy">Each allocation reprices its own customer-facing quote revision</div>
                </article>
            </section>
        @endif

        @if ($isCreateScreen && ! $hasAvailableDraftRevisions)
            <section class="panel">
                <p class="empty-state"><strong>No draft transport quote revisions are ready for shared-load planning yet.</strong></p>
                <p class="empty-state">Create a new transport quote, or return an existing quote to draft before building a shared run.</p>
                <div class="action-links">
                    <a class="button button-outline" href="{{ route('quotes.create') }}">Create a new transport quote</a>
                </div>
            </section>
        @else
        <form class="form-grid" method="POST" action="{{ $formAction }}">
            @csrf

            @if ($method !== 'POST')
                @method($method)
            @endif

            <section class="panel">
                <div class="page-header">
                    <div>
                        <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="truck" size="18" /></span>Parent shared run</h2>
                        <p class="section-copy">Use one parent record for the operational run, then attach each customer allocation below.</p>
                    </div>
                </div>

                <div class="split-grid">
                    <label>
                        Run name
                        <input name="name" type="text" value="{{ old('name', $sharedRun->name) }}">
                    </label>
                    <label>
                        Run date
                        <input name="run_date" type="date" value="{{ old('run_date', $sharedRun->run_date?->format('Y-m-d')) }}">
                    </label>
                </div>

                <label>
                    Shared run notes
                    <textarea name="notes">{{ old('notes', $sharedRun->notes) }}</textarea>
                </label>
            </section>

            @foreach ($allocationRows as $allocationIndex => $allocationRow)
                @php
                    $selectedRevision = $availableDraftRevisionsById->get((int) ($allocationRow['job_revision_id'] ?? 0));
                    $routeLegs = $selectedRevision?->routeLegs?->keyBy('sequence') ?? collect();
                    $allocationSummary = $allocationSummaries->get((int) ($allocationRow['job_revision_id'] ?? 0));
                @endphp
                <section class="panel">
                    <div class="page-header">
                        <div>
                            <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="document" size="18" /></span>Customer allocation {{ $allocationIndex + 1 }}</h2>
                            <p class="section-copy">Assign full and split mileage for each standard route leg, then record why those miles belong to this customer.</p>
                        </div>
                        @if ($allocationSummary)
                            <span class="badge badge-active">£{{ number_format((float) $allocationSummary->total_charge, 2) }}</span>
                        @endif
                    </div>

                    <label>
                        Draft quote revision
                        <select name="allocations[{{ $allocationIndex }}][job_revision_id]" required>
                            <option value="">Select a draft quote revision</option>
                            @foreach ($availableDraftRevisions as $revisionOption)
                                <option
                                    value="{{ $revisionOption->id }}"
                                    @selected((string) old("allocations.$allocationIndex.job_revision_id", $allocationRow['job_revision_id'] ?? '') === (string) $revisionOption->id)
                                >
                                    Revision {{ $revisionOption->revision_number }}:
                                    {{ $revisionOption->job->customer->name }}
                                    ({{ $revisionOption->pickup_postcode }} to {{ $revisionOption->dropoff_postcode }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Leg</th>
                                <th>Stored route miles</th>
                                <th>Full miles</th>
                                <th>Split miles</th>
                                <th>Split divisor</th>
                                <th>Reasoning</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allocationRow['legs'] as $legIndex => $leg)
                                @php
                                    $routeLeg = $routeLegs->get($legIndex + 1);
                                @endphp
                                <tr>
                                    <td>{{ $routeLegTitles[$leg['label']] ?? str_replace('_', ' ', $leg['label']) }}</td>
                                    <td>{{ $routeLeg?->manual_miles ?? $routeLeg?->miles ?? 'Select a revision' }}</td>
                                    <td>
                                        <input
                                            name="allocations[{{ $allocationIndex }}][legs][{{ $legIndex }}][full_miles]"
                                            min="0"
                                            step="1"
                                            type="number"
                                            value="{{ old("allocations.$allocationIndex.legs.$legIndex.full_miles", $leg['full_miles']) }}"
                                            required
                                        >
                                    </td>
                                    <td>
                                        <input
                                            name="allocations[{{ $allocationIndex }}][legs][{{ $legIndex }}][split_miles]"
                                            min="0"
                                            step="1"
                                            type="number"
                                            value="{{ old("allocations.$allocationIndex.legs.$legIndex.split_miles", $leg['split_miles']) }}"
                                            required
                                        >
                                    </td>
                                    <td>
                                        <input
                                            name="allocations[{{ $allocationIndex }}][legs][{{ $legIndex }}][split_divisor]"
                                            min="2"
                                            step="1"
                                            type="number"
                                            value="{{ old("allocations.$allocationIndex.legs.$legIndex.split_divisor", $leg['split_divisor']) }}"
                                        >
                                    </td>
                                    <td>
                                        <textarea
                                            name="allocations[{{ $allocationIndex }}][legs][{{ $legIndex }}][reason]"
                                            required
                                        >{{ old("allocations.$allocationIndex.legs.$legIndex.reason", $leg['reason']) }}</textarea>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($allocationSummary)
                        <div class="detail-grid">
                            <article class="metric">
                                <div class="metric-label">Full charge miles</div>
                                <div class="metric-value">{{ $allocationSummary->full_charge_miles }}</div>
                                <div class="metric-copy">Miles charged fully to this customer</div>
                            </article>
                            <article class="metric">
                                <div class="metric-label">Split charge miles</div>
                                <div class="metric-value">{{ $allocationSummary->split_charge_miles }}</div>
                                <div class="metric-copy">Miles shared only where the route genuinely overlaps</div>
                            </article>
                            <article class="metric">
                                <div class="metric-label">Calculated total</div>
                                <div class="metric-value">£{{ number_format((float) $allocationSummary->total_charge, 2) }}</div>
                                <div class="metric-copy">Stored on the linked quote revision as the engine total</div>
                            </article>
                        </div>
                    @endif
                </section>
            @endforeach

            <div class="form-actions">
                <button class="button button-primary" type="submit">
                    {{ $sharedRun->exists ? 'Update shared run' : 'Save shared run' }}
                </button>
            </div>
        </form>
        @endif
    </main>
@endsection
