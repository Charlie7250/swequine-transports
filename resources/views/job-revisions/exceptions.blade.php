@extends('layouts.app', ['title' => 'Quote exceptions'])

@php
    $resolutionLegs = $revision->routeResolution->legs->keyBy('sequence');
@endphp

@section('content')
    <main class="page">
        <section class="hero">
            <span class="eyebrow">Quote workspace</span>
            <h1 class="page-title">Quote exceptions for revision {{ $revision->revision_number }}</h1>
            <p class="page-copy">Original route and engine values stay visible. A change creates a new draft revision with a complete audit record.</p>
        </section>

        @if ($canOverrideRouteLegs)
            <section class="panel">
                <h2 class="section-title">Adjust a disputed route leg</h2>
                <p class="section-copy">Enter only the legs that need a replacement mile value. Each changed leg needs its own category and explanation.</p>

                <form class="form-grid" method="POST" action="{{ route('jobs.revisions.route-leg-overrides', [$job, $revision]) }}">
                    @csrf
                    @foreach ($revision->routeLegs as $index => $routeLeg)
                        <section class="panel">
                            <h3 class="section-title">{{ str_replace('_', ' ', $routeLeg->label) }}</h3>
                            <p class="field-help">Original route value: {{ $resolutionLegs->get($routeLeg->sequence)?->quoted_miles ?? 'Unavailable' }}</p>
                            <label>
                                Replacement miles
                                <input class="field-input" name="overrides[{{ $index }}][miles]" type="number" min="1" step="1" value="{{ old("overrides.$index.miles") }}">
                            </label>
                            <label>
                                Reason category
                                <select name="overrides[{{ $index }}][reason_category]">
                                    <option value="">Select a reason</option>
                                    <option value="postcode_ambiguity" @selected(old("overrides.$index.reason_category") === 'postcode_ambiguity')>Postcode ambiguity</option>
                                    <option value="disputed_mileage" @selected(old("overrides.$index.reason_category") === 'disputed_mileage')>Disputed mileage</option>
                                    <option value="operational_exception" @selected(old("overrides.$index.reason_category") === 'operational_exception')>Operational exception</option>
                                </select>
                            </label>
                            <label>
                                Explanation
                                <textarea name="overrides[{{ $index }}][explanation]">{{ old("overrides.$index.explanation") }}</textarea>
                            </label>
                        </section>
                    @endforeach
                    <div class="form-actions">
                        <button class="button button-primary" type="submit">Apply route leg changes</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="panel">
            <h2 class="section-title">Final total override</h2>
            <p class="section-copy">Engine total: £{{ number_format((float) $revision->engine_total, 2) }}. The engine result stays in the quote record.</p>

            <form class="form-grid" method="POST" action="{{ route('jobs.revisions.final-total-override', [$job, $revision]) }}">
                @csrf
                <label>
                    Final total
                    <input class="field-input" name="final_total" type="number" min="0.01" step="0.01" value="{{ old('final_total') }}" required>
                </label>
                <label>
                    Reason category
                    <select name="reason_category" required>
                        <option value="">Select a reason</option>
                        <option value="commercial_adjustment" @selected(old('reason_category') === 'commercial_adjustment')>Commercial adjustment</option>
                        <option value="customer_agreement" @selected(old('reason_category') === 'customer_agreement')>Customer agreement</option>
                        <option value="goodwill_adjustment" @selected(old('reason_category') === 'goodwill_adjustment')>Goodwill adjustment</option>
                    </select>
                </label>
                <label>
                    Explanation
                    <textarea name="explanation" required>{{ old('explanation') }}</textarea>
                </label>
                <div class="form-actions">
                    <button class="button button-primary" type="submit">Apply final total override</button>
                </div>
            </form>
        </section>
    </main>
@endsection
