@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Phase 1 and Phase 2</span>
            <h1 class="page-title">Deterministic horse quotes, with the spreadsheet rules made visible.</h1>
            <p class="page-copy">
                This internal app starts with explicit route legs, revision history, weekly fuel pricing, and auditable totals.
                Shared loads and loading practice stay modelled separately so the pricing core remains understandable.
            </p>
        </section>

        <section class="card-grid">
            <article class="metric">
                <div class="metric-label">Transport jobs</div>
                <div class="metric-value">{{ $jobCount }}</div>
                <div class="metric-copy">Revisioned jobs will carry issued, booked, and completed reporting dates.</div>
            </article>

            <article class="metric">
                <div class="metric-label">Active fuel input</div>
                <div class="metric-value">
                    {{ $activeFuelPrice?->price_per_litre_inc_vat ? '£' . number_format((float) $activeFuelPrice->price_per_litre_inc_vat, 4) : 'Pending' }}
                </div>
                <div class="metric-copy">
                    {{ $activeFuelPrice?->week_commencing?->format('j M Y') ?? 'Seed a weekly fuel entry to activate quote calculations.' }}
                </div>
            </article>

            <article class="metric">
                <div class="metric-label">Active rate setting</div>
                <div class="metric-value">{{ $activeRateSetting?->depot_postcode ?? 'Pending' }}</div>
                <div class="metric-copy">
                    {{ $activeRateSetting?->name ?? 'Seed the initial depot postcode and rate assumptions for pricing.' }}
                </div>
            </article>
        </section>

        <section class="panel">
            <h2 class="section-title">Current implementation focus</h2>
            <ul class="bullet-list">
                <li>Use three explicit route legs for quote pricing, unloaded, loaded, then unloaded.</li>
                <li>Retain engine totals and final quoted totals separately for audit and reporting.</li>
                <li>Keep shared-run allocations and loading-practice records in separate tables.</li>
                <li>Make weekly fuel inputs and rate settings easy to update without touching view code.</li>
            </ul>
        </section>
    </main>
@endsection
