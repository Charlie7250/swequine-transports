@extends('layouts.app', ['title' => 'Review transport route'])

@php
    $legLabels = [
        'depot_to_pickup' => 'Depot to pickup',
        'pickup_to_drop_off' => 'Pickup to drop-off',
        'drop_off_to_depot' => 'Drop-off to depot',
    ];
    $depotPostcode = $pricingContext['rate_setting']?->depot_postcode
        ?? data_get($resolution->raw_input_snapshot, 'depot_postcode');
    $routeWarnings = $resolution->warnings ?? [];
    $routeIssues = array_filter($routeEvidence, fn (array $evidence): bool => $evidence['failure_message'] !== null);
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Transport quote</span>
            <h1 class="page-title">Review calculated route</h1>
            <p class="page-copy">{{ $statusMessage }}</p>
        </section>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="document" size="18" /></span>Enquiry</h2>
                    <p class="section-copy">Draft enquiry for {{ $enquiry->customer_name }}</p>
                </div>
                <span class="badge {{ $resolution->pricing_eligible ? 'badge-active' : 'badge-muted' }}">
                    {{ str_replace('_', ' ', $resolution->overall_status) }}
                </span>
            </div>

            <dl class="detail-list">
                <div>
                    <dt>Depot postcode</dt>
                    <dd>{{ $depotPostcode ?? 'Not configured' }}</dd>
                </div>
                <div>
                    <dt>Route source</dt>
                    <dd>{{ strtoupper($resolution->provider ?? 'Unknown') }}</dd>
                </div>
                <div>
                    <dt>Resolved at</dt>
                    <dd>{{ $resolution->resolved_at?->format('j M Y H:i') ?? 'Not resolved' }}</dd>
                </div>
            </dl>
        </section>

        <section class="panel">
            <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="truck" size="18" /></span>Route legs</h2>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Leg</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Miles</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resolution->legs as $leg)
                        <tr>
                            <td>{{ $legLabels[$leg->leg_type] ?? $leg->leg_type }}</td>
                            <td>{{ $leg->origin_input }}</td>
                            <td>{{ $leg->destination_input }}</td>
                            <td>{{ $leg->quoted_miles ?? 'Unavailable' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        @if ($canHandleException)
            <section class="panel">
                <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="truck" size="18" /></span>Location review</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Leg</th>
                            <th>Entered origin</th>
                            <th>Resolved origin</th>
                            <th>Entered destination</th>
                            <th>Resolved destination</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($routeEvidence as $evidence)
                            <tr>
                                <td>{{ $legLabels[$evidence['leg_type']] ?? $evidence['leg_type'] }}</td>
                                <td>{{ $evidence['entered_origin'] }}</td>
                                <td>{{ $evidence['resolved_origin'] }}</td>
                                <td>{{ $evidence['entered_destination'] }}</td>
                                <td>{{ $evidence['resolved_destination'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        @if ($routeIssues !== [])
            <section class="panel">
                <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="settings" size="18" /></span>Route issues</h2>
                <ul class="bullet-list">
                    @foreach ($routeIssues as $issue)
                        <li>{{ $legLabels[$issue['leg_type']] ?? $issue['leg_type'] }}: {{ $issue['failure_message'] }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($routeWarnings !== [])
            <section class="panel">
                <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="settings" size="18" /></span>Route warnings</h2>
                <ul class="bullet-list">
                    @foreach ($routeWarnings as $warning)
                        <li>{{ $warning['message'] }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($requiresManualPricingReview)
            <section class="panel">
                <p class="empty-state">Automatic transport pricing supports one or two horses. Counts above two require manual review.</p>
            </section>
        @elseif ($canAccept)
            <form class="form-actions" method="POST" action="{{ route('transport-enquiries.route-accept', [$enquiry, $resolution]) }}">
                @csrf
                <button class="button button-primary" type="submit">
                    {{ $resolution->overall_status === 'operator_review_required' ? 'Accept reviewed route for quote' : 'Use this route for quote' }}
                </button>
            </form>
        @elseif (in_array($resolution->overall_status, ['resolved', 'resolved_with_warning'], true))
            <section class="panel">
                <p class="empty-state">This quote cannot be priced because the active fuel or rate context is unavailable. Ask the responsible staff member to update it.</p>
            </section>
        @endif

        @if ($canHandleException)
            <section class="panel">
                <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="settings" size="18" /></span>Route exception</h2>
                <p class="section-copy">Retry or correct the route first. Exception controls keep the recorded route evidence and require a reason for every changed value.</p>
                <div class="form-actions">
                    <a class="button button-outline" href="{{ route('transport-enquiries.route-exception', [$enquiry, $resolution]) }}">Handle route exception</a>
                </div>
            </section>
        @endif
    </main>
@endsection
