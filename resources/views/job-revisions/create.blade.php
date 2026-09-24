@extends('layouts.app', ['title' => 'New quote'])

@php
    $canSaveDraft = $pricingContext['weekly_fuel_price'] !== null && $pricingContext['rate_setting'] !== null;
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Transport quotes</span>
            <h1 class="page-title">Create a transport quote</h1>
            <p class="page-copy">
                Start a draft quote with customer details, fixed three-leg mileage entry, and immediate pricing against the active fuel and rate records.
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

        @include('job-revisions._workspace-form', [
            'formAction' => route('quotes.store'),
            'method' => 'POST',
            'submitLabel' => 'Save draft quote',
            'canSubmit' => $canSaveDraft,
            'submitBlockedMessage' => 'Quotes cannot be saved until one active weekly fuel entry and one active rate setting are in place.',
            'submitBlockedLinks' => [
                ['label' => 'Add weekly fuel entry', 'href' => route('admin.weekly-fuel-prices.index')],
                ['label' => 'Add rate setting', 'href' => route('admin.rate-settings.index')],
            ],
        ])

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title">Active weekly fuel default for pricing</h2>
                    <p class="section-copy">A new draft will store this record the first time it is priced.</p>
                </div>
            </div>

            @if ($pricingContext['weekly_fuel_price'])
                <div class="detail-grid">
                    <article class="metric">
                        <div class="metric-label">Week commencing</div>
                        <div class="metric-value">{{ $pricingContext['weekly_fuel_price']->week_commencing?->format('j M Y') }}</div>
                        <div class="metric-copy">{{ $pricingContext['weekly_fuel_price']->source }}</div>
                    </article>
                    <article class="metric">
                        <div class="metric-label">Price per litre inc VAT</div>
                        <div class="metric-value">£{{ number_format((float) $pricingContext['weekly_fuel_price']->price_per_litre_inc_vat, 4) }}</div>
                        <div class="metric-copy">This is the current active fuel input.</div>
                    </article>
                </div>
            @else
                <p class="empty-state">No active weekly fuel record is available for pricing yet. Add one before saving transport quotes.</p>
            @endif
        </section>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title">Active rate setting default for pricing</h2>
                    <p class="section-copy">The depot postcode and active rates come from this stored settings record.</p>
                </div>
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
                                <dt>Unloaded add on</dt>
                                <dd>{{ number_format((float) $pricingContext['rate_setting']->unloaded_add_on_per_mile, 6) }}</dd>
                            </div>
                            <div>
                                <dt>Loaded add on</dt>
                                <dd>{{ number_format((float) $pricingContext['rate_setting']->loaded_add_on_per_mile, 6) }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            @else
                <p class="empty-state">No active rate setting is available for pricing yet. Add one before saving transport quotes.</p>
            @endif
        </section>
    </main>
@endsection
