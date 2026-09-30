@php
    $quote = $quote ?? null;
    $hasManualOverride = collect($quote?->calculation_explanation['overrides'] ?? [])
        ->contains(fn (array $override): bool => ($override['type'] ?? null) === 'manual_final_total');
    $manualFinalTotalValue = $quote !== null
        && $quote->final_total !== null
        && $hasManualOverride
            ? $quote->final_total
            : null;
    $canSubmit = $canSubmit ?? true;
    $submitBlockedMessage = $submitBlockedMessage ?? null;
    $submitBlockedLinks = $submitBlockedLinks ?? [];
@endphp

<form class="form-grid" method="POST" action="{{ $formAction }}">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="panel">
        <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="document" size="18" /></span>Customer details</h2>

        <div class="split-grid">
            <label>
                Customer name
                <input @class(['field-input', 'is-invalid' => $errors->has('customer_name')]) name="customer_name" type="text" value="{{ old('customer_name', $quote?->customer?->name ?? '') }}" required>
            </label>
            <label>
                Contact name
                <input @class(['field-input', 'is-invalid' => $errors->has('customer_contact_name')]) name="customer_contact_name" type="text" value="{{ old('customer_contact_name', $quote?->customer?->contact_name ?? '') }}">
            </label>
            <label>
                Email
                <input @class(['field-input', 'is-invalid' => $errors->has('customer_email')]) name="customer_email" type="email" value="{{ old('customer_email', $quote?->customer?->email ?? '') }}">
            </label>
            <label>
                Phone
                <input @class(['field-input', 'is-invalid' => $errors->has('customer_phone')]) name="customer_phone" type="text" value="{{ old('customer_phone', $quote?->customer?->phone ?? '') }}">
            </label>
            <label>
                Customer postcode
                <input @class(['field-input', 'is-invalid' => $errors->has('customer_postcode')]) name="customer_postcode" type="text" value="{{ old('customer_postcode', $quote?->customer?->postcode ?? '') }}">
            </label>
        </div>

        <label>
            Customer notes
            <textarea name="customer_notes">{{ old('customer_notes', $quote?->customer?->notes ?? '') }}</textarea>
        </label>
    </section>

    <section class="panel">
        <h2 class="section-title"><span class="section-icon"><x-prototype-icon name="chart" size="18" /></span>Loading-practice pricing</h2>

        <div class="split-grid">
            <label>
                Travel miles
                <input @class(['field-input', 'is-invalid' => $errors->has('travel_miles')]) name="travel_miles" min="1" step="1" type="number" value="{{ old('travel_miles', $quote?->travel_miles ?? '') }}" required>
                @error('travel_miles')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                On-site hours
                <input @class(['field-input', 'is-invalid' => $errors->has('on_site_hours')]) name="on_site_hours" min="0" step="0.01" type="number" value="{{ old('on_site_hours', $quote?->on_site_hours ?? '') }}">
            </label>
            <label>
                Handling and loading livery period
                <select @class(['is-invalid' => $errors->has('handling_livery_period')]) name="handling_livery_period">
                    <option value="">None</option>
                    <option value="day" @selected(old('handling_livery_period', $quote?->handling_livery_period) === 'day')>Day</option>
                    <option value="week" @selected(old('handling_livery_period', $quote?->handling_livery_period) === 'week')>Week</option>
                    <option value="fortnight" @selected(old('handling_livery_period', $quote?->handling_livery_period) === 'fortnight')>Fortnight</option>
                </select>
            </label>
            <label>
                Handling and loading livery quantity
                <input @class(['field-input', 'is-invalid' => $errors->has('handling_livery_quantity')]) name="handling_livery_quantity" min="1" step="1" type="number" value="{{ old('handling_livery_quantity', $quote?->handling_livery_quantity ?? '') }}">
            </label>
            <label>
                Manual final total
                <input @class(['field-input', 'is-invalid' => $errors->has('manual_final_total')]) name="manual_final_total" min="0.01" step="0.01" type="number" value="{{ old('manual_final_total', $manualFinalTotalValue ?? '') }}">
                @error('manual_final_total')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>
                Manual final total reason
                <input @class(['field-input', 'is-invalid' => $errors->has('manual_final_total_reason')]) name="manual_final_total_reason" type="text" value="{{ old('manual_final_total_reason', $quote?->manual_final_total_reason ?? '') }}">
                @error('manual_final_total_reason')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <label>
            Quote notes
            <textarea name="quote_notes">{{ old('quote_notes', $quote?->notes ?? '') }}</textarea>
        </label>
    </section>

    @if ($canSubmit)
        <div class="form-actions">
            <button class="button button-primary" type="submit">{{ $submitLabel }}</button>
        </div>
    @elseif ($submitBlockedMessage)
        <section class="panel">
            <p class="empty-state"><strong>{{ $submitBlockedMessage }}</strong></p>
            @if ($submitBlockedLinks !== [])
                <div class="action-links">
                    @foreach ($submitBlockedLinks as $link)
                        <a class="button button-outline" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</form>
