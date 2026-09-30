@extends('layouts.app', ['title' => 'New loading-practice quote'])

@php
    $canSaveDraft = $pricingContext['rate_setting'] !== null;
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Loading practice</span>
            <h1 class="page-title">Create a loading-practice quote</h1>
            <p class="page-copy">
                Keep loading practice separate from transport quoting, while still using stored package pricing and a visible calculation record.
            </p>
        </section>

        @if ($errors->any())
            <section class="error-banner">
                <ul class="bullet-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @include('loading-practice-quotes._form', [
            'formAction' => route('loading-practice-quotes.store'),
            'method' => 'POST',
            'submitLabel' => 'Save loading-practice quote',
            'canSubmit' => $canSaveDraft,
            'submitBlockedMessage' => 'Loading-practice quotes cannot be saved until one active rate setting is in place.',
            'submitBlockedLinks' => [
                ['label' => 'Add rate setting', 'href' => route('admin.rate-settings.index')],
            ],
        ])

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="settings" size="18" /></span>Active rate setting for loading practice</h2>
                    <p class="section-copy">A new loading-practice quote will store this package configuration when it is first priced.</p>
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
                <p class="empty-state">No active rate setting is available for loading-practice pricing yet. Add one before saving loading-practice quotes.</p>
            @endif
        </section>
    </main>
@endsection
