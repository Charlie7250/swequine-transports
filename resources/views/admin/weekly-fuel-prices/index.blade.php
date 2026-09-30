@extends('layouts.app', ['title' => 'Weekly fuel prices'])

@php
    use App\Models\FuelPriceSource;
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Fuel admin</span>
            <h1 class="page-title">Weekly fuel price control</h1>
            <p class="page-copy">
                Record each weekly fuel input, keep the history visible, and decide which entry is active for new quote pricing.
            </p>
        </section>

        @if (session('status'))
            <div class="status-banner">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-banner">
                <ul class="bullet-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="fuel" size="18" /></span>Current active fuel input</h2>
                    <p class="section-copy">This entry becomes the default fuel record for new pricing runs unless a revision already stores a different one.</p>
                </div>

                @if ($activeFuelPrice)
                    <span class="badge badge-active">Active</span>
                @endif
            </div>

            @if ($activeFuelPrice)
                <div class="summary-grid">
                    <article class="metric">
                        <div class="metric-label">Week commencing</div>
                        <div class="metric-value">{{ $activeFuelPrice->week_commencing?->format('j M Y') }}</div>
                        <div class="metric-copy">{{ FuelPriceSource::labelFor($activeFuelPrice->source) }}</div>
                    </article>
                    <article class="metric">
                        <div class="metric-label">Price per litre inc VAT</div>
                        <div class="metric-value">£{{ number_format((float) $activeFuelPrice->price_per_litre_inc_vat, 4) }}</div>
                        <div class="metric-copy">
                            Activated {{ $activeFuelPrice->activated_at?->format('j M Y H:i') ?? 'Not yet activated' }}
                        </div>
                    </article>
                </div>
            @else
                <p class="empty-state">No weekly fuel entry is active yet. Add one below so transport quotes can be priced.</p>
            @endif
        </section>

        <section class="panel">
            <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="fuel" size="18" /></span>Add a weekly fuel entry</h2>

            <form class="form-section" method="POST" action="{{ route('admin.weekly-fuel-prices.store') }}">
                @csrf

                <div class="split-grid">
                    <label>
                        <span>Week commencing</span>
                        <input name="week_commencing" type="date" value="{{ old('week_commencing') }}" required>
                    </label>

                    <label>
                        <span>Source</span>
                        <select name="source" required>
                            @foreach ($sources as $fuelPriceSource)
                                <option value="{{ $fuelPriceSource->key }}" @selected(old('source') === $fuelPriceSource->key)>{{ $fuelPriceSource->display_name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Price per litre inc VAT</span>
                        <input name="price_per_litre_inc_vat" type="number" step="0.0001" min="0.0001" value="{{ old('price_per_litre_inc_vat') }}" required>
                    </label>
                </div>

                <label class="inline-row">
                    <input name="activate_now" type="checkbox" value="1" @checked(old('activate_now', true))>
                    <span>Make this the active fuel input now</span>
                </label>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Save weekly fuel entry</button>
                    <span class="field-help">Use the history table below to manually override the current active entry later.</span>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="fuel" size="18" /></span>Add a fuel source</h2>

            <form class="form-section" method="POST" action="{{ route('admin.fuel-price-sources.store') }}">
                @csrf

                <div class="split-grid">
                    <label>
                        <span>Source name</span>
                        <input name="display_name" type="text" value="{{ old('display_name') }}" required>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="button button-outline" type="submit">Save fuel source</button>
                    <span class="field-help">New sources become selectable in the weekly fuel entry dropdown above.</span>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="fuel" size="18" /></span>Weekly fuel history</h2>

            @if ($weeklyFuelPrices->isEmpty())
                <p class="empty-state">No weekly fuel history has been recorded yet.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Week</th>
                            <th>Source</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weeklyFuelPrices as $weeklyFuelPrice)
                            <tr>
                                <td>{{ $weeklyFuelPrice->week_commencing?->format('j M Y') }}</td>
                                <td>{{ FuelPriceSource::labelFor($weeklyFuelPrice->source) }}</td>
                                <td>£{{ number_format((float) $weeklyFuelPrice->price_per_litre_inc_vat, 4) }}</td>
                                <td>
                                    <span class="badge {{ $weeklyFuelPrice->is_active ? 'badge-active' : 'badge-muted' }}">
                                        {{ $weeklyFuelPrice->is_active ? 'Active' : 'History' }}
                                    </span>
                                </td>
                                <td>
                                    @if (! $weeklyFuelPrice->is_active)
                                        <form method="POST" action="{{ route('admin.weekly-fuel-prices.activate', $weeklyFuelPrice) }}">
                                            @csrf
                                            <button class="button-inline" type="submit">Set as active override</button>
                                        </form>
                                    @else
                                        <span class="muted">In use for new quotes</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>
@endsection
