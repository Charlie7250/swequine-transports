@extends('layouts.app', ['title' => 'Handle route exception'])

@php
    $legLabels = [
        'depot_to_pickup' => 'Depot to pickup',
        'pickup_to_drop_off' => 'Pickup to drop-off',
        'drop_off_to_depot' => 'Drop-off to depot',
    ];
    $routeIssues = array_filter($routeEvidence, fn (array $evidence): bool => $evidence['failure_message'] !== null);
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Transport quote</span>
            <h1 class="page-title">Handle route exception</h1>
            <p class="page-copy">The recorded route remains part of the quote evidence. Retry or correct it before using an approved manual fallback.</p>
        </section>

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

        <section class="panel">
            <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="truck" size="18" /></span>Recorded route values</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Leg</th>
                        <th>Original route value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resolution->legs as $leg)
                        <tr>
                            <td>{{ $legLabels[$leg->leg_type] ?? $leg->leg_type }}</td>
                            <td>{{ $leg->quoted_miles ?? 'Unavailable' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="panel">
            <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="settings" size="18" /></span>Retry or correct the route</h2>
            <div class="form-actions">
                <form method="POST" action="{{ route('transport-enquiries.route-retry', [$enquiry, $resolution]) }}">
                    @csrf
                    <button class="button button-outline" type="submit">Retry route lookup</button>
                </form>
            </div>

            <form class="form-grid" method="POST" action="{{ route('transport-enquiries.route-correct', [$enquiry, $resolution]) }}">
                @csrf
                <label>
                    Pickup postcode
                    <input class="field-input" name="pickup_postcode" type="text" value="{{ old('pickup_postcode', $enquiry->pickup_postcode) }}" required>
                </label>
                <label>
                    Drop-off postcode
                    <input class="field-input" name="dropoff_postcode" type="text" value="{{ old('dropoff_postcode', $enquiry->dropoff_postcode) }}" required>
                </label>
                <div class="form-actions">
                    <button class="button button-primary" type="submit">Correct and resolve again</button>
                </div>
            </form>
        </section>

        @if ($canUseManualFallback)
            <section class="panel">
                <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="settings" size="18" /></span>Use manual miles because route lookup is unavailable</h2>
                <p class="section-copy">Enter all three legs. Every replacement value is recorded with your reason and the original route value.</p>

                <form class="form-grid" method="POST" action="{{ route('transport-enquiries.manual-fallback', [$enquiry, $resolution]) }}">
                    @csrf
                    <label>
                        Reason category
                        <select name="reason_category" required>
                            <option value="">Select a reason</option>
                            <option value="provider_outage" @selected(old('reason_category') === 'provider_outage')>Provider outage</option>
                            <option value="postcode_ambiguity" @selected(old('reason_category') === 'postcode_ambiguity')>Postcode ambiguity</option>
                            <option value="provider_response_invalid" @selected(old('reason_category') === 'provider_response_invalid')>Invalid provider response</option>
                            <option value="disputed_mileage" @selected(old('reason_category') === 'disputed_mileage')>Disputed mileage</option>
                            <option value="operational_exception" @selected(old('reason_category') === 'operational_exception')>Operational exception</option>
                        </select>
                    </label>
                    <label>
                        Explanation
                        <textarea name="explanation" required>{{ old('explanation') }}</textarea>
                    </label>
                    @foreach ($resolution->legs as $index => $leg)
                        <label>
                            {{ $legLabels[$leg->leg_type] ?? $leg->leg_type }} miles
                            <input class="field-input" name="route_legs[{{ $index }}][miles]" type="number" min="1" step="1" value="{{ old("route_legs.$index.miles") }}" required>
                        </label>
                    @endforeach
                    <div class="form-actions">
                        <button class="button button-primary" type="submit">Apply manual route miles</button>
                    </div>
                </form>
            </section>
        @endif
    </main>
@endsection
