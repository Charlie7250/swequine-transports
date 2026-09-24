@extends('layouts.app', ['title' => 'Loading-practice quote'])

@php
    $explanation = $quote->calculation_explanation ?? [];
    $hasManualOverride = collect($explanation['overrides'] ?? [])
        ->contains(fn (array $override): bool => ($override['type'] ?? null) === 'manual_final_total');
    $manualOverrideCopy = $quote->manual_final_total_reason
        ?: ($hasManualOverride ? 'Manual final total override recorded.' : 'No manual final total override recorded.');
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Loading practice</span>
            <h1 class="page-title">{{ $quote->customer->name }}</h1>
            <p class="page-copy">
                This quote stays outside the transport workflow and keeps package, POA, and manual pricing decisions visible in one place.
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
                <div class="metric-label">Status</div>
                <div class="metric-value">{{ ucfirst($quote->status) }}</div>
                <div class="metric-copy">{{ str_replace('_', ' ', $quote->distance_band ?? 'pending') }}</div>
            </article>

            <article class="metric">
                <div class="metric-label">Engine total</div>
                <div class="metric-value">{{ $quote->engine_total !== null ? '£' . number_format((float) $quote->engine_total, 2) : 'POA' }}</div>
                <div class="metric-copy">{{ $quote->is_poa ? 'Manual amount required for over-50-mile travel.' : 'Stored calculated total' }}</div>
            </article>

            <article class="metric">
                <div class="metric-label">Final quoted total</div>
                <div class="metric-value">{{ $quote->final_total !== null ? '£' . number_format((float) $quote->final_total, 2) : 'POA' }}</div>
                <div class="metric-copy">{{ $manualOverrideCopy }}</div>
            </article>
        </section>

        @include('loading-practice-quotes._form', [
            'quote' => $quote,
            'formAction' => route('loading-practice-quotes.update', $quote),
            'method' => 'PATCH',
            'submitLabel' => 'Save loading-practice quote',
        ])

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title">
                        {{ $pricingContext['rate_setting_is_stored'] ? 'Rate setting used for this quote' : 'Current rate setting default for pricing' }}
                    </h2>
                    <p class="section-copy">{{ $pricingContext['rate_setting_origin'] }}</p>
                </div>
            </div>

            @if ($pricingContext['rate_setting'])
                <div class="detail-grid">
                    <article class="metric">
                        <div class="metric-label">Rate setting</div>
                        <div class="metric-value">{{ $pricingContext['rate_setting']->name }}</div>
                        <div class="metric-copy">{{ $pricingContext['rate_setting']->depot_postcode }}</div>
                    </article>
                    <article class="panel">
                        <dl class="detail-list">
                            <div>
                                <dt>Within 15 miles</dt>
                                <dd>£{{ number_format((float) $pricingContext['rate_setting']->loading_practice_within_15_miles_price, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Within 25 miles</dt>
                                <dd>£{{ number_format((float) $pricingContext['rate_setting']->loading_practice_within_25_miles_price, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Within 50 miles</dt>
                                <dd>£{{ number_format((float) $pricingContext['rate_setting']->loading_practice_within_50_miles_price, 2) }}</dd>
                            </div>
                            <div>
                                <dt>On-site hourly rate</dt>
                                <dd>£{{ number_format((float) $pricingContext['rate_setting']->loading_practice_on_site_hourly_rate, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Livery day rate</dt>
                                <dd>£{{ number_format((float) $pricingContext['rate_setting']->loading_practice_livery_day_rate, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Livery week rate</dt>
                                <dd>£{{ number_format((float) $pricingContext['rate_setting']->loading_practice_livery_week_rate, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Livery fortnight rate</dt>
                                <dd>£{{ number_format((float) $pricingContext['rate_setting']->loading_practice_livery_fortnight_rate, 2) }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            @else
                <p class="empty-state">No rate setting record has been resolved for this loading-practice quote yet.</p>
            @endif
        </section>

        @if ($explanation)
            <details class="panel" open>
                <summary class="section-title">Calculation explanation</summary>

                <div class="detail-grid">
                    <article class="panel">
                        <h2 class="section-title">Package breakdown</h2>
                        <dl class="detail-list">
                            <div>
                                <dt>Travel miles</dt>
                                <dd>{{ $explanation['travel_miles'] ?? 'Pending' }}</dd>
                            </div>
                            <div>
                                <dt>Distance band</dt>
                                <dd>{{ str_replace('_', ' ', $explanation['distance_band'] ?? 'pending') }}</dd>
                            </div>
                            <div>
                                <dt>Package price</dt>
                                <dd>{{ ($explanation['package_price'] ?? null) !== null ? '£' . $explanation['package_price'] : 'POA' }}</dd>
                            </div>
                            <div>
                                <dt>On-site amount</dt>
                                <dd>£{{ $explanation['on_site']['amount'] ?? '0.00' }}</dd>
                            </div>
                            <div>
                                <dt>Handling and loading livery</dt>
                                <dd>£{{ $explanation['handling_livery']['amount'] ?? '0.00' }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article class="panel">
                        <h2 class="section-title">Totals</h2>
                        <dl class="detail-list">
                            <div>
                                <dt>Engine total</dt>
                                <dd>{{ ($explanation['engine_total'] ?? null) !== null ? '£' . $explanation['engine_total'] : 'POA' }}</dd>
                            </div>
                            <div>
                                <dt>Final total</dt>
                                <dd>{{ ($explanation['final_total'] ?? null) !== null ? '£' . $explanation['final_total'] : 'POA' }}</dd>
                            </div>
                            <div>
                                <dt>Overrides</dt>
                                <dd>{{ empty($explanation['overrides']) ? 'None' : count($explanation['overrides']) }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </details>
        @endif
    </main>
@endsection
