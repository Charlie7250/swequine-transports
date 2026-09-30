@extends('layouts.app', ['title' => 'Quote workspace'])

@php
    $sharedRunAllocation = $revision->sharedRunAllocation;
    $sharedLoadExplanation = $revision->calculation_explanation['shared_load'] ?? null;
    $isCurrentWorkingRevision = (int) $job->current_working_revision_id === (int) $revision->id;
    $isAutomaticRoute = $revision->routeResolution !== null;
    $hasManualRouteException = $revision->quoteExceptionAudits->contains(fn ($audit) => in_array($audit->exception_type, ['manual_route_fallback', 'route_leg_override'], true));
    $hasFinalTotalOverride = $revision->quoteExceptionAudits->contains(fn ($audit) => $audit->exception_type === 'final_total_override');
    $issueChecklistRequirements = collect($issueChecklist)->reject(fn ($item) => $item['label'] === 'Quote review confirmation');
    $issueChecklistIsComplete = $issueChecklistRequirements->every(fn ($item) => $item['complete']);
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Quote workspace</span>
            <h1 class="page-title">Revision {{ $revision->revision_number }} for {{ $job->customer->name }}</h1>
            <p class="page-copy">
                @if ($isCurrentWorkingRevision)
                    This workspace keeps customer details, explicit route legs, pricing records, and status actions in one place for the current quote revision.
                @else
                    This is a historical revision snapshot. Use it to compare previous pricing, notes, and route details against the current working revision.
                @endif
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

        <section class="card-grid">
            <article class="metric">
                <div class="metric-label">Job status</div>
                <div class="metric-value"><x-status-badge :status="$job->status" class="status-badge--lg" /></div>
                <div class="metric-copy">{{ config("job_statuses.{$job->status}.meaning", 'Pickup '.($revision->pickup_postcode ?? 'Pending').' to '.($revision->dropoff_postcode ?? 'Pending')) }}</div>
            </article>

            <article class="metric">
                <div class="metric-label">Engine total</div>
                <div class="metric-value">£{{ number_format((float) $revision->engine_total, 2) }}</div>
                <div class="metric-copy">Stored calculated total</div>
            </article>

            <article class="metric metric--accent">
                <div class="metric-label">Final quoted total</div>
                <div class="metric-value">£{{ number_format((float) ($revision->final_total ?? $revision->engine_total), 2) }}</div>
                <div class="metric-copy">{{ $revision->manual_final_total_reason ?: 'No manual final total override recorded.' }}</div>
            </article>
        </section>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="check" size="18" /></span>Quote actions</h2>
                    <p class="section-copy">Use the issue and pipeline actions here, the workspace form below keeps the revision content and totals up to date.</p>
                </div>
                <x-status-badge :status="$job->status" />
            </div>

            @if (! $isCurrentWorkingRevision)
                <p class="field-help">This revision is part of the audit trail. Only the current working revision can move through the pipeline.</p>
            @elseif ($job->status === 'draft')
                <div class="form-actions">
                    <form method="POST" action="{{ route('jobs.revisions.issue', ['job' => $job, 'revision' => $revision]) }}">
                        @csrf
                        <input type="hidden" name="confirmed_revision_id" value="{{ $revision->id }}">
                        @if ($isAutomaticRoute)
                            <section class="form-section">
                                <div>
                                    <h3 class="section-title">Issue checklist</h3>
                                    <ul class="bullet-list">
                                        @foreach ($issueChecklist as $item)
                                            <li>{{ $item['label'] }}: {{ $item['complete'] ? 'Ready' : 'Needs attention' }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </section>
                        @endif
                        <label class="inline-row">
                            <input type="checkbox" name="issue_confirmation" value="1" required>
                            I confirm that revision {{ $revision->revision_number }} is the reviewed quote to issue.
                        </label>
                        <button class="button button-primary" type="submit" @disabled(! $issueChecklistIsComplete)>Issue quote</button>
                    </form>
                    @if ($isAutomaticRoute)
                        <a class="button button-outline" href="{{ route('jobs.revisions.exceptions', ['job' => $job, 'revision' => $revision]) }}">Manage quote exceptions</a>
                    @endif
                </div>
            @elseif ($job->status === 'quoted')
                <div class="form-actions">
                    <a class="button button-outline" href="{{ route('jobs.revisions.issued', ['job' => $job, 'revision' => $revision]) }}">View issued quote</a>
                    <form method="POST" action="{{ route('jobs.revisions.pending', ['job' => $job, 'revision' => $revision]) }}">
                        @csrf
                        <button class="button button-outline" type="submit">Mark pending</button>
                    </form>
                    <form method="POST" action="{{ route('jobs.revisions.book', ['job' => $job, 'revision' => $revision]) }}">
                        @csrf
                        <button class="button button-primary" type="submit">Mark booked</button>
                    </form>
                    <form method="POST" action="{{ route('jobs.revisions.draft', ['job' => $job, 'revision' => $revision]) }}">
                        @csrf
                        <button class="button button-outline" type="submit">Return to draft</button>
                    </form>
                </div>
            @elseif ($job->status === 'pending')
                <div class="form-actions">
                    <form method="POST" action="{{ route('jobs.revisions.book', ['job' => $job, 'revision' => $revision]) }}">
                        @csrf
                        <button class="button button-primary" type="submit">Mark booked</button>
                    </form>
                    <form method="POST" action="{{ route('jobs.revisions.draft', ['job' => $job, 'revision' => $revision]) }}">
                        @csrf
                        <button class="button button-outline" type="submit">Return to draft</button>
                    </form>
                </div>
            @elseif ($job->status === 'booked')
                <div class="form-actions">
                    <form method="POST" action="{{ route('jobs.revisions.complete', ['job' => $job, 'revision' => $revision]) }}">
                        @csrf
                        <button class="button button-primary" type="submit">Mark completed</button>
                    </form>
                </div>
            @else
                <p class="field-help">No further pipeline actions are available for this status.</p>
            @endif
        </section>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="fuel" size="18" /></span>Fuel price record</h2>
                    <p class="section-copy">The selected record stays with this revision. A correction creates a new draft revision.</p>
                </div>
                <span class="badge badge-muted">{{ $revision->weeklyFuelPrice?->week_commencing?->format('j M Y') ?? 'Not selected' }}</span>
            </div>

            @if ($isCurrentWorkingRevision && $job->status === 'draft')
                <form class="form-grid" method="POST" action="{{ route('jobs.revisions.fuel-price', ['job' => $job, 'revision' => $revision]) }}">
                    @csrf
                    <label>
                        Corrected weekly fuel price
                        <select @class(['field-input', 'is-invalid' => $errors->has('weekly_fuel_price_id')]) name="weekly_fuel_price_id" required>
                            @foreach ($weeklyFuelPrices as $weeklyFuelPrice)
                                <option value="{{ $weeklyFuelPrice->id }}" @selected((int) old('weekly_fuel_price_id', $revision->weekly_fuel_price_id) === $weeklyFuelPrice->id)>
                                    {{ $weeklyFuelPrice->week_commencing->format('j M Y') }}: £{{ number_format((float) $weeklyFuelPrice->price_per_litre_inc_vat, 4) }} from {{ \App\Models\FuelPriceSource::labelFor($weeklyFuelPrice->source) }}
                                </option>
                            @endforeach
                        </select>
                        @error('weekly_fuel_price_id')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                    <div class="form-actions">
                        <button class="button button-outline" type="submit">Create corrected draft revision</button>
                    </div>
                </form>
            @else
                <p class="field-help">Only the current draft revision can select a corrected fuel price.</p>
            @endif
        </section>

        <section class="card-grid">
            <article class="metric">
                <div class="metric-label">Issued date</div>
                <div class="metric-value">{{ $revision->issued_at?->format('j M Y') ?? 'Pending' }}</div>
                <div class="metric-copy">{{ $revision->issued_at?->format('H:i') ?? 'Not issued yet' }}</div>
            </article>

            <article class="metric">
                <div class="metric-label">Booked date</div>
                <div class="metric-value">{{ $job->booked_at?->format('j M Y') ?? 'Pending' }}</div>
                <div class="metric-copy">{{ $job->booked_at?->format('H:i') ?? 'Not booked yet' }}</div>
            </article>

            <article class="metric">
                <div class="metric-label">Completed date</div>
                <div class="metric-value">{{ $job->completed_at?->format('j M Y') ?? 'Pending' }}</div>
                <div class="metric-copy">{{ $job->completed_at?->format('H:i') ?? 'Not completed yet' }}</div>
            </article>
        </section>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="chart" size="18" /></span>Revision history</h2>
                    <p class="section-copy">Each revision keeps a structured snapshot of the quote path, totals, and notes that were current at that point in the workflow.</p>
                </div>
                <span class="badge badge-muted">{{ $job->revisions->count() }} revisions</span>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Revision</th>
                        <th>Route</th>
                        <th>Stored total</th>
                        <th>Recorded</th>
                        <th>Markers</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($job->revisions as $historicalRevision)
                        <tr>
                            <td>
                                <a class="button-inline" href="{{ route('jobs.revisions.show', ['job' => $job, 'revision' => $historicalRevision]) }}">
                                    Revision {{ $historicalRevision->revision_number }}
                                </a>
                            </td>
                            <td>{{ $historicalRevision->pickup_postcode ?? 'Pending' }} to {{ $historicalRevision->dropoff_postcode ?? 'Pending' }}</td>
                            <td>£{{ number_format((float) ($historicalRevision->final_total ?? $historicalRevision->engine_total ?? 0), 2) }}</td>
                            <td>{{ $historicalRevision->created_at?->format('j M Y H:i') ?? 'Pending' }}</td>
                            <td>
                                <div class="form-actions">
                                    @if ((int) $job->current_working_revision_id === (int) $historicalRevision->id)
                                        <span class="badge badge-active">Current working revision</span>
                                    @endif
                                    @if ((int) $job->issued_revision_id === (int) $historicalRevision->id)
                                        <span class="badge badge-muted">Issued revision</span>
                                    @endif
                                    @if ($historicalRevision->issued_at)
                                        <a class="button-inline" href="{{ route('jobs.revisions.issued', ['job' => $job, 'revision' => $historicalRevision]) }}">View issued quote</a>
                                    @endif
                                    @if ((int) $job->accepted_revision_id === (int) $historicalRevision->id)
                                        <span class="badge badge-muted">Accepted revision</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="truck" size="18" /></span>Shared run builder</h2>
                    <p class="section-copy">Use a parent shared run when this customer shares only part of the route and still needs their own auditable quote total.</p>
                </div>
                @if ($sharedRunAllocation)
                    <span class="badge badge-active">Linked</span>
                @else
                    <span class="badge badge-muted">Not linked</span>
                @endif
            </div>

            @if ($sharedRunAllocation)
                <div class="detail-grid">
                    <article class="metric">
                        <div class="metric-label">Shared run</div>
                        <div class="metric-value">{{ $sharedRunAllocation->sharedRun?->name ?: 'Shared run ' . $sharedRunAllocation->shared_run_id }}</div>
                        <div class="metric-copy">
                            {{ $sharedRunAllocation->sharedRun?->run_date?->format('j M Y') ?? 'Run date pending' }}
                        </div>
                    </article>
                    <article class="metric">
                        <div class="metric-label">Full charge miles</div>
                        <div class="metric-value">{{ $sharedRunAllocation->full_charge_miles }}</div>
                        <div class="metric-copy">Miles charged fully to this customer</div>
                    </article>
                    <article class="metric">
                        <div class="metric-label">Split charge miles</div>
                        <div class="metric-value">{{ $sharedRunAllocation->split_charge_miles }}</div>
                        <div class="metric-copy">Miles shared only where the route genuinely overlaps</div>
                    </article>
                </div>

                <div class="form-actions">
                    <a class="button button-outline" href="{{ route('shared-runs.show', ['sharedRun' => $sharedRunAllocation->shared_run_id]) }}">
                        View shared run
                    </a>
                </div>
            @elseif ($job->status === 'draft')
                <div class="form-actions">
                    <a class="button button-outline" href="{{ route('shared-runs.create', ['seed_revision' => $revision->id]) }}">
                        Build shared run
                    </a>
                </div>
            @else
                <p class="field-help">Return this quote to draft before attaching it to a shared run.</p>
            @endif
        </section>

        @if ($isAutomaticRoute)
            <section class="panel">
                <div class="page-header">
                    <div>
                        <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="truck" size="18" /></span>Automatic route</h2>
                        <p class="section-copy">This draft uses the recorded automatic route as its pricing evidence.</p>
                    </div>
                    <span class="badge badge-active">{{ str_replace('_', ' ', $revision->routeResolution->overall_status) }}</span>
                </div>

                <dl class="detail-list">
                    <div>
                        <dt>Route source</dt>
                        <dd>{{ strtoupper($revision->routeResolution->provider ?? 'Unknown') }}</dd>
                    </div>
                    <div>
                        <dt>Resolved at</dt>
                        <dd>{{ $revision->routeResolution->resolved_at?->format('j M Y H:i') ?? 'Not recorded' }}</dd>
                    </div>
                </dl>
            </section>
        @else
            @include('job-revisions._workspace-form', [
                'job' => $job,
                'revision' => $revision,
                'formAction' => route('jobs.revisions.update', ['job' => $job, 'revision' => $revision]),
                'method' => 'PATCH',
                'submitLabel' => 'Save quote workspace',
            ])
        @endif

        @if ($revision->quoteExceptionAudits->isNotEmpty())
            <section class="panel">
                <div class="page-header">
                    <div>
                        <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="settings" size="18" /></span>Exception audit</h2>
                        <p class="section-copy">Each change retains its original value, replacement value, reason, operator, and recorded time.</p>
                    </div>
                    <div class="form-actions">
                        @if ($hasManualRouteException)
                            <span class="badge badge-muted">Manual route miles</span>
                        @endif
                        @if ($hasFinalTotalOverride)
                            <span class="badge badge-muted">Final total overridden</span>
                        @endif
                    </div>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Exception</th>
                            <th>Original route value</th>
                            <th>Replacement value</th>
                            <th>Reason</th>
                            <th>Operator</th>
                            <th>Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($revision->quoteExceptionAudits as $audit)
                            <tr>
                                <td>{{ str_replace('_', ' ', $audit->exception_type) }}</td>
                                <td>{{ $audit->original_value ?? 'Unavailable' }}</td>
                                <td>{{ $audit->replacement_value }}</td>
                                <td>{{ $audit->reason_category }}: {{ $audit->explanation }}</td>
                                <td>{{ $audit->actor?->name ?? 'Not recorded' }}</td>
                                <td>{{ $audit->recorded_at?->format('j M Y H:i') ?? 'Not recorded' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="fuel" size="18" /></span>
                        {{ $pricingContext['weekly_fuel_price_is_stored'] ? 'Weekly fuel used for this quote' : 'Current weekly fuel default for pricing' }}
                    </h2>
                    <p class="section-copy">
                        {{ $pricingContext['weekly_fuel_price_is_stored'] ? $pricingContext['weekly_fuel_price_origin'] : 'This revision has not been priced yet. If you price it now, this active fuel input will be used.' }}
                    </p>
                </div>
                <span class="badge {{ $pricingContext['weekly_fuel_price']?->is_active ? 'badge-active' : 'badge-muted' }}">
                    {{ $pricingContext['weekly_fuel_price']?->is_active ? 'Active now' : 'Historical record' }}
                </span>
            </div>

            @if ($pricingContext['weekly_fuel_price'])
                <div class="detail-grid">
                    <article class="metric">
                        <div class="metric-label">Week commencing</div>
                        <div class="metric-value">{{ $pricingContext['weekly_fuel_price']->week_commencing?->format('j M Y') }}</div>
                        <div class="metric-copy">{{ \App\Models\FuelPriceSource::labelFor($pricingContext['weekly_fuel_price']->source) }}</div>
                    </article>
                    <article class="metric">
                        <div class="metric-label">Price per litre inc VAT</div>
                        <div class="metric-value">£{{ number_format((float) $pricingContext['weekly_fuel_price']->price_per_litre_inc_vat, 4) }}</div>
                        <div class="metric-copy">
                            Activated {{ $pricingContext['weekly_fuel_price']->activated_at?->format('j M Y H:i') ?? 'Not activated' }}
                        </div>
                    </article>
                </div>
            @else
                <p class="empty-state">No weekly fuel record has been resolved for this revision yet.</p>
            @endif
        </section>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="settings" size="18" /></span>
                        {{ $pricingContext['rate_setting_is_stored'] ? 'Rate setting used for this quote' : 'Current rate setting default for pricing' }}
                    </h2>
                    <p class="section-copy">
                        {{ $pricingContext['rate_setting_is_stored'] ? $pricingContext['rate_setting_origin'] : 'This revision has not been priced yet. If you price it now, this active rate setting will be used.' }}
                    </p>
                </div>
                <span class="badge {{ $pricingContext['rate_setting']?->is_active ? 'badge-active' : 'badge-muted' }}">
                    {{ $pricingContext['rate_setting']?->is_active ? 'Active now' : 'Historical record' }}
                </span>
            </div>

            @if ($pricingContext['rate_setting'])
                <div class="detail-grid">
                    <article class="metric">
                        <div class="metric-label">Setting</div>
                        <div class="metric-value">{{ $pricingContext['rate_setting']->name }}</div>
                        <div class="metric-copy">{{ $pricingContext['rate_setting']->depot_postcode }}</div>
                    </article>
                    <article class="panel">
                        <dl class="detail-list">
                            <div>
                                <dt>Miles per gallon</dt>
                                <dd>{{ number_format((float) $pricingContext['rate_setting']->miles_per_gallon, 4) }}</dd>
                            </div>
                            <div>
                                <dt>Litres per gallon</dt>
                                <dd>{{ number_format((float) $pricingContext['rate_setting']->litres_per_gallon, 4) }}</dd>
                            </div>
                            <div>
                                <dt>Maintenance per mile</dt>
                                <dd>{{ number_format((float) $pricingContext['rate_setting']->maintenance_per_mile, 6) }}</dd>
                            </div>
                            <div>
                                <dt>Unloaded add on</dt>
                                <dd>{{ number_format((float) $pricingContext['rate_setting']->unloaded_add_on_per_mile, 6) }}</dd>
                            </div>
                            <div>
                                <dt>Loaded add on</dt>
                                <dd>{{ number_format((float) $pricingContext['rate_setting']->loaded_add_on_per_mile, 6) }}</dd>
                            </div>
                            <div>
                                <dt>Shared load percentage</dt>
                                <dd>{{ number_format((float) $pricingContext['rate_setting']->shared_load_percentage * 100, 2) }}%</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            @else
                <p class="empty-state">No rate setting record has been resolved for this revision yet.</p>
            @endif
        </section>

        <section class="panel">
            <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="truck" size="18" /></span>{{ $sharedRunAllocation ? 'Priced route legs for this allocation' : 'Priced route legs' }}</h2>

            @if ($revision->routeLegs->isEmpty())
                <p class="empty-state">No route legs have been recorded for this revision yet.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Leg</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Miles used</th>
                            <th>Rate type</th>
                            <th>Rate per mile</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($revision->routeLegs as $routeLeg)
                            <tr>
                                <td>{{ str_replace('_', ' ', $routeLeg->label) }}</td>
                                <td>{{ $routeLeg->start_postcode ?? 'Pending' }}</td>
                                <td>{{ $routeLeg->end_postcode ?? 'Pending' }}</td>
                                <td>{{ $routeLeg->manual_miles ?? $routeLeg->miles }}</td>
                                <td>{{ ucfirst($routeLeg->rate_type) }}</td>
                                <td>{{ $routeLeg->rate_per_mile !== null ? '£' . number_format((float) $routeLeg->rate_per_mile, 6) : 'Pending' }}</td>
                                <td>{{ $routeLeg->amount !== null ? '£' . number_format((float) $routeLeg->amount, 2) : 'Pending' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        @if ($revision->calculation_explanation)
            <details class="panel">
                <summary class="section-title"><span class="section-icon"><x-prototype-icon name="chart" size="18" /></span>Calculation explanation</summary>

                <div class="detail-grid">
                    <article class="panel">
                        <h3 class="section-title">Fuel context</h3>
                        <dl class="detail-list">
                            <div>
                                <dt>Week commencing</dt>
                                <dd>{{ $revision->calculation_explanation['fuel_context']['week_commencing'] }}</dd>
                            </div>
                            <div>
                                <dt>Source</dt>
                                <dd>{{ \App\Models\FuelPriceSource::labelFor($revision->calculation_explanation['fuel_context']['source']) }}</dd>
                            </div>
                            <div>
                                <dt>Price per litre inc VAT</dt>
                                <dd>£{{ $revision->calculation_explanation['fuel_context']['price_per_litre_inc_vat'] }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article class="panel">
                        <h3 class="section-title">Rate inputs</h3>
                        <dl class="detail-list">
                            @foreach ($revision->calculation_explanation['rate_inputs'] as $label => $value)
                                <div>
                                    <dt>{{ str_replace('_', ' ', $label) }}</dt>
                                    <dd>{{ is_numeric($value) && str_contains((string) $label, 'percentage') ? number_format((float) $value * 100, 2) . '%' : $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </article>

                    <article class="panel">
                        <h3 class="section-title">Resolved rates</h3>
                        <dl class="detail-list">
                            <div>
                                <dt>Base cost per mile</dt>
                                <dd>£{{ $revision->calculation_explanation['resolved_rates']['base_cost_per_mile'] }}</dd>
                            </div>
                            <div>
                                <dt>Unloaded rate per mile</dt>
                                <dd>£{{ $revision->calculation_explanation['resolved_rates']['unloaded_rate_per_mile'] }}</dd>
                            </div>
                            @if (isset($revision->calculation_explanation['resolved_rates']['base_loaded_rate_per_mile']))
                                <div>
                                    <dt>Base loaded rate per mile</dt>
                                    <dd>£{{ $revision->calculation_explanation['resolved_rates']['base_loaded_rate_per_mile'] }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>Horse-adjusted loaded rate per mile</dt>
                                <dd>£{{ $revision->calculation_explanation['resolved_rates']['loaded_rate_per_mile'] }}</dd>
                            </div>
                            @if (isset($revision->calculation_explanation['horse_count']))
                                <div>
                                    <dt>Horse count and multiplier</dt>
                                    <dd>{{ $revision->calculation_explanation['horse_count']['count'] }} at {{ $revision->calculation_explanation['horse_count']['multiplier'] }}</dd>
                                </div>
                            @endif
                        </dl>
                    </article>

                    <article class="panel">
                        <h3 class="section-title">Totals</h3>
                        <dl class="detail-list">
                            <div>
                                <dt>Engine total</dt>
                                <dd>£{{ $revision->calculation_explanation['engine_total'] }}</dd>
                            </div>
                            <div>
                                <dt>Final total</dt>
                                <dd>£{{ $revision->calculation_explanation['final_total'] }}</dd>
                            </div>
                            <div>
                                <dt>Overrides</dt>
                                <dd>{{ empty($revision->calculation_explanation['overrides']) ? 'None' : count($revision->calculation_explanation['overrides']) }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <article class="panel">
                    <h3 class="section-title">Leg calculations</h3>
                    <dl class="detail-list">
                        @foreach ($revision->calculation_explanation['legs'] as $leg)
                            <div>
                                <dt>{{ str_replace('_', ' ', $leg['label']) }}</dt>
                                <dd>{{ $leg['miles'] ?? $leg['route_miles'] }} miles at £{{ $leg['rate_per_mile'] }} = £{{ $leg['amount'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </article>

                @if (! empty($revision->calculation_explanation['overrides']))
                    <article class="panel">
                        <h3 class="section-title">Manual final total override</h3>
                        <dl class="detail-list">
                            @foreach ($revision->calculation_explanation['overrides'] as $override)
                                <div>
                                    <dt>{{ str_replace('_', ' ', $override['type']) }}</dt>
                                    <dd>£{{ $override['amount'] }}</dd>
                                </div>
                            @endforeach
                            <div>
                                <dt>Reason</dt>
                                <dd>{{ $revision->manual_final_total_reason ?? 'Not recorded' }}</dd>
                            </div>
                        </dl>
                    </article>
                @endif
            </details>
        @endif

        @if ($sharedLoadExplanation)
            <section class="panel">
                <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="truck" size="18" /></span>Shared allocation reasoning</h2>
                @if (! empty($sharedLoadExplanation['shared_load_percentage']))
                    <p class="section-copy">
                        Split loaded miles were priced at {{ number_format((float) $sharedLoadExplanation['shared_load_percentage'] * 100, 2) }}% of the loaded rate.
                    </p>
                @endif

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Leg</th>
                            <th>Full miles</th>
                            <th>Split miles</th>
                            <th>Split divisor</th>
                            <th>Charged amount</th>
                            <th>Reasoning</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sharedLoadExplanation['allocation_legs'] as $allocationLeg)
                            <tr>
                                <td>{{ str_replace('_', ' ', $allocationLeg['label']) }}</td>
                                <td>{{ $allocationLeg['full_miles'] }}</td>
                                <td>{{ $allocationLeg['split_miles'] }}</td>
                                <td>{{ $allocationLeg['split_divisor'] ?? 'Not split' }}</td>
                                <td>£{{ $allocationLeg['amount'] }}</td>
                                <td>{{ $allocationLeg['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </main>
@endsection
