@extends('layouts.app', ['title' => 'Rate settings'])

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Rate admin</span>
            <h1 class="page-title">Rate settings control</h1>
            <p class="page-copy">
                Maintain the visible transport assumptions that drive the weekly unloaded and loaded rates, without moving pricing logic into the UI.
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
                    <h2 class="section-title">Current active rate setting</h2>
                    <p class="section-copy">New pricing runs resolve these values unless a quote revision already stores a specific rate setting record.</p>
                </div>

                @if ($activeRateSetting)
                    <span class="badge badge-active">Active</span>
                @endif
            </div>

            @if ($activeRateSetting)
                <div class="detail-grid">
                    <article class="metric">
                        <div class="metric-label">Setting</div>
                        <div class="metric-value">{{ $activeRateSetting->name }}</div>
                        <div class="metric-copy">{{ $activeRateSetting->depot_postcode }}</div>
                    </article>
                    <article class="panel">
                        <h3 class="section-title">Transport rate inputs</h3>
                        <dl class="detail-list">
                            <div>
                                <dt>Miles per gallon</dt>
                                <dd>{{ number_format((float) $activeRateSetting->miles_per_gallon, 4) }}</dd>
                            </div>
                            <div>
                                <dt>Litres per gallon</dt>
                                <dd>{{ number_format((float) $activeRateSetting->litres_per_gallon, 4) }}</dd>
                            </div>
                            <div>
                                <dt>Maintenance per mile</dt>
                                <dd>{{ number_format((float) $activeRateSetting->maintenance_per_mile, 6) }}</dd>
                            </div>
                            <div>
                                <dt>Unloaded add on</dt>
                                <dd>{{ number_format((float) $activeRateSetting->unloaded_add_on_per_mile, 6) }}</dd>
                            </div>
                            <div>
                                <dt>Loaded add on</dt>
                                <dd>{{ number_format((float) $activeRateSetting->loaded_add_on_per_mile, 6) }}</dd>
                            </div>
                            <div>
                                <dt>One horse multiplier</dt>
                                <dd>{{ $activeRateSetting->one_horse_multiplier === null ? 'Not configured' : number_format((float) $activeRateSetting->one_horse_multiplier, 6) }}</dd>
                            </div>
                            <div>
                                <dt>Two horse multiplier</dt>
                                <dd>{{ $activeRateSetting->two_horse_multiplier === null ? 'Not configured' : number_format((float) $activeRateSetting->two_horse_multiplier, 6) }}</dd>
                            </div>
                            <div>
                                <dt>Shared load percentage</dt>
                                <dd>{{ number_format((float) $activeRateSetting->shared_load_percentage * 100, 2) }}%</dd>
                            </div>
                        </dl>

                        <h3 class="section-title">Loading practice prices</h3>
                        <dl class="detail-list">
                            <div>
                                <dt>Loading practice within 15 miles</dt>
                                <dd>£{{ number_format((float) $activeRateSetting->loading_practice_within_15_miles_price, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Loading practice within 25 miles</dt>
                                <dd>£{{ number_format((float) $activeRateSetting->loading_practice_within_25_miles_price, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Loading practice within 50 miles</dt>
                                <dd>£{{ number_format((float) $activeRateSetting->loading_practice_within_50_miles_price, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Loading practice on-site hourly rate</dt>
                                <dd>£{{ number_format((float) $activeRateSetting->loading_practice_on_site_hourly_rate, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Loading practice livery day rate</dt>
                                <dd>£{{ number_format((float) $activeRateSetting->loading_practice_livery_day_rate, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Loading practice livery week rate</dt>
                                <dd>£{{ number_format((float) $activeRateSetting->loading_practice_livery_week_rate, 2) }}</dd>
                            </div>
                            <div>
                                <dt>Loading practice livery fortnight rate</dt>
                                <dd>£{{ number_format((float) $activeRateSetting->loading_practice_livery_fortnight_rate, 2) }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            @else
                <p class="empty-state">No active rate setting is in place yet. Add one below so quoting can start.</p>
            @endif
        </section>

        <section class="panel">
            <h2 class="section-title">Add a rate setting record</h2>

            <form class="form-section" method="POST" action="{{ route('admin.rate-settings.store') }}">
                @csrf

                <h3 class="section-title">Transport rate inputs</h3>
                <div class="split-grid">
                    <label>
                        <span>Name</span>
                        <input name="name" type="text" value="{{ old('name') }}" required>
                    </label>

                    <label>
                        <span>Depot postcode</span>
                        <input name="depot_postcode" type="text" value="{{ old('depot_postcode') }}" required>
                    </label>

                    <label>
                        <span>Effective from</span>
                        <input name="effective_from" type="date" value="{{ old('effective_from') }}" required>
                    </label>

                    <label>
                        <span>Effective until</span>
                        <input name="effective_until" type="date" value="{{ old('effective_until') }}">
                    </label>

                    <label>
                        <span>Miles per gallon</span>
                        <input name="miles_per_gallon" type="number" step="0.0001" min="0.0001" value="{{ old('miles_per_gallon') }}" required>
                    </label>

                    <label>
                        <span>Litres per gallon</span>
                        <input name="litres_per_gallon" type="number" step="0.0001" min="0.0001" value="{{ old('litres_per_gallon') }}" required>
                    </label>

                    <label>
                        <span>Maintenance per mile</span>
                        <input name="maintenance_per_mile" type="number" step="0.000001" min="0" value="{{ old('maintenance_per_mile') }}" required>
                    </label>

                    <label>
                        <span>Unloaded add on per mile</span>
                        <input name="unloaded_add_on_per_mile" type="number" step="0.000001" min="0" value="{{ old('unloaded_add_on_per_mile') }}" required>
                    </label>

                    <label>
                        <span>Loaded add on per mile</span>
                        <input name="loaded_add_on_per_mile" type="number" step="0.000001" min="0" value="{{ old('loaded_add_on_per_mile') }}" required>
                    </label>

                    <label>
                        <span>One horse multiplier</span>
                        <input name="one_horse_multiplier" type="number" step="0.000001" min="0.000001" value="{{ old('one_horse_multiplier', '1.500000') }}" required>
                    </label>

                    <label>
                        <span>Shared load percentage</span>
                        <input name="shared_load_percentage" type="number" step="0.000001" min="0.000001" value="{{ old('shared_load_percentage', '0.750000') }}" required>
                    </label>

                    <label>
                        <span>Two horse multiplier</span>
                        <input name="two_horse_multiplier" type="number" step="0.000001" min="0.000001" value="{{ old('two_horse_multiplier', '1.750000') }}" required>
                    </label>
                </div>

                <h3 class="section-title">Loading practice prices</h3>
                <div class="split-grid">
                    <label>
                        <span>Loading practice within 15 miles</span>
                        <input name="loading_practice_within_15_miles_price" type="number" step="0.01" min="0" value="{{ old('loading_practice_within_15_miles_price', '30.00') }}" required>
                    </label>

                    <label>
                        <span>Loading practice within 25 miles</span>
                        <input name="loading_practice_within_25_miles_price" type="number" step="0.01" min="0" value="{{ old('loading_practice_within_25_miles_price', '45.00') }}" required>
                    </label>

                    <label>
                        <span>Loading practice within 50 miles</span>
                        <input name="loading_practice_within_50_miles_price" type="number" step="0.01" min="0" value="{{ old('loading_practice_within_50_miles_price', '90.00') }}" required>
                    </label>

                    <label>
                        <span>Loading practice on-site hourly rate</span>
                        <input name="loading_practice_on_site_hourly_rate" type="number" step="0.01" min="0" value="{{ old('loading_practice_on_site_hourly_rate', '25.00') }}" required>
                    </label>

                    <label>
                        <span>Loading practice livery day rate</span>
                        <input name="loading_practice_livery_day_rate" type="number" step="0.01" min="0" value="{{ old('loading_practice_livery_day_rate', '35.00') }}" required>
                    </label>

                    <label>
                        <span>Loading practice livery week rate</span>
                        <input name="loading_practice_livery_week_rate" type="number" step="0.01" min="0" value="{{ old('loading_practice_livery_week_rate', '220.00') }}" required>
                    </label>

                    <label>
                        <span>Loading practice livery fortnight rate</span>
                        <input name="loading_practice_livery_fortnight_rate" type="number" step="0.01" min="0" value="{{ old('loading_practice_livery_fortnight_rate', '410.00') }}" required>
                    </label>
                </div>

                <label class="inline-row">
                    <input name="activate_now" type="checkbox" value="1" @checked(old('activate_now', true))>
                    <span>Make this the active rate setting now</span>
                </label>

                <div class="form-actions">
                    <button class="button button-primary" type="submit">Save rate setting</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2 class="section-title">Recorded rate settings</h2>

            @if ($rateSettings->isEmpty())
                <p class="empty-state">No rate settings have been recorded yet.</p>
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Effective from</th>
                            <th>Depot</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rateSettings as $rateSetting)
                            <tr>
                                <td>{{ $rateSetting->name }}</td>
                                <td>{{ $rateSetting->effective_from?->format('j M Y') ?? 'Not set' }}</td>
                                <td>{{ $rateSetting->depot_postcode }}</td>
                                <td>
                                    <span class="badge {{ $rateSetting->is_active ? 'badge-active' : 'badge-muted' }}">
                                        {{ $rateSetting->is_active ? 'Active' : 'Stored' }}
                                    </span>
                                </td>
                                <td>
                                    @if (! $rateSetting->is_active)
                                        <form method="POST" action="{{ route('admin.rate-settings.activate', $rateSetting) }}">
                                            @csrf
                                            <button class="button-inline" type="submit">Set as active record</button>
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
