@php
    $revision = $revision ?? null;
    $job = $job ?? null;
    $isCurrentWorkingRevision = $job === null || $revision === null || (int) $job->current_working_revision_id === (int) $revision->id;
    $isEditable = $job === null || ($job->status === 'draft' && $isCurrentWorkingRevision);
    $manualFinalTotalValue = $revision !== null
        && $revision->final_total !== null
        && (
            $revision->manual_final_total_reason !== null
            || (float) $revision->final_total !== (float) $revision->engine_total
        )
            ? $revision->final_total
            : null;
    $canManageQuoteExceptions = auth()->user()?->canManageQuoteExceptions() ?? false;
    $manualFinalTotalAudit = $revision?->quoteExceptionAudits
        ?->where('exception_type', 'final_total_override')
        ->sortByDesc('recorded_at')
        ->first();
    $routeLegDefinitions = [
        ['sequence' => 1, 'label' => 'depot_to_pickup', 'title' => 'Depot to pickup', 'copy' => 'Unloaded travel from the active or stored depot postcode.', 'rate_type' => 'unloaded'],
        ['sequence' => 2, 'label' => 'pickup_to_dropoff', 'title' => 'Pickup to drop-off', 'copy' => 'Loaded travel with at least one horse on board.', 'rate_type' => 'loaded'],
        ['sequence' => 3, 'label' => 'dropoff_to_depot', 'title' => 'Drop-off to depot', 'copy' => 'Unloaded return to the depot postcode.', 'rate_type' => 'unloaded'],
    ];
    $routeLegsBySequence = $revision?->routeLegs?->keyBy('sequence') ?? collect();
    $canSubmit = $canSubmit ?? true;
    $submitBlockedMessage = $submitBlockedMessage ?? null;
    $submitBlockedLinks = $submitBlockedLinks ?? [];
@endphp

<style>
    .workspace-fields {
        margin: 0;
        padding: 0;
        border: 0;
        min-inline-size: 0;
        display: grid;
        gap: 1rem;
    }

    .workspace-fields[disabled] .panel {
        background: rgba(255, 255, 255, 0.82);
    }

    .workspace-fields[disabled] input,
    .workspace-fields[disabled] select,
    .workspace-fields[disabled] textarea {
        background: var(--stone-50);
        color: var(--ink-700);
        cursor: not-allowed;
    }
</style>

<form class="form-grid" method="POST" action="{{ $formAction }}">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <fieldset class="workspace-fields" @disabled(! $isEditable)>
        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title">Customer details</h2>
                    <p class="section-copy">Capture the customer record alongside the operational quote notes for this revision.</p>
                </div>
                @if (isset($job))
                    <span class="badge {{ $job->status === 'quoted' ? 'badge-active' : 'badge-muted' }}">{{ ucfirst($job->status) }}</span>
                @endif
            </div>

            <div class="split-grid">
                <label>
                    Customer name
                    <input @class(['field-input', 'is-invalid' => $errors->has('customer_name')]) name="customer_name" type="text" value="{{ old('customer_name', $revision?->job?->customer?->name ?? '') }}" required>
                </label>
                <label>
                    Contact name
                    <input @class(['field-input', 'is-invalid' => $errors->has('customer_contact_name')]) name="customer_contact_name" type="text" value="{{ old('customer_contact_name', $revision?->job?->customer?->contact_name ?? '') }}">
                </label>
                <label>
                    Email
                    <input @class(['field-input', 'is-invalid' => $errors->has('customer_email')]) name="customer_email" type="email" value="{{ old('customer_email', $revision?->job?->customer?->email ?? '') }}">
                </label>
                <label>
                    Phone
                    <input @class(['field-input', 'is-invalid' => $errors->has('customer_phone')]) name="customer_phone" type="text" value="{{ old('customer_phone', $revision?->job?->customer?->phone ?? '') }}">
                </label>
                <label>
                    Customer postcode
                    <input @class(['field-input', 'is-invalid' => $errors->has('customer_postcode')]) name="customer_postcode" type="text" value="{{ old('customer_postcode', $revision?->job?->customer?->postcode ?? '') }}">
                </label>
                <label>
                    Horse count
                    <input @class(['field-input', 'is-invalid' => $errors->has('horse_count')]) name="horse_count" min="1" max="2" type="number" value="{{ old('horse_count', $revision?->horse_count ?? 1) }}" required>
                </label>
            </div>

            <label>
                Customer notes
                <textarea name="customer_notes">{{ old('customer_notes', $revision?->job?->customer?->notes ?? '') }}</textarea>
            </label>
        </section>

        <section class="panel">
            <h2 class="section-title">Route and quote details</h2>

            <div class="split-grid">
                <label>
                    Pickup postcode
                    <input @class(['field-input', 'is-invalid' => $errors->has('pickup_postcode')]) name="pickup_postcode" type="text" value="{{ old('pickup_postcode', $revision?->pickup_postcode ?? '') }}" required>
                </label>
                <label>
                    Drop-off postcode
                    <input @class(['field-input', 'is-invalid' => $errors->has('dropoff_postcode')]) name="dropoff_postcode" type="text" value="{{ old('dropoff_postcode', $revision?->dropoff_postcode ?? '') }}" required>
                </label>
                @if ($canManageQuoteExceptions)
                    <label>
                        Manual final total
                        <input @class(['field-input', 'is-invalid' => $errors->has('manual_final_total')]) name="manual_final_total" min="0.01" step="0.01" type="number" value="{{ old('manual_final_total', $manualFinalTotalValue ?? '') }}">
                        @error('manual_final_total')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                    <label>
                        Manual final total reason category
                        <select @class(['field-input', 'is-invalid' => $errors->has('manual_final_total_reason_category')]) name="manual_final_total_reason_category">
                            <option value="">Select a category</option>
                            @foreach (['commercial_adjustment' => 'Commercial adjustment', 'customer_agreement' => 'Customer agreement', 'goodwill_adjustment' => 'Goodwill adjustment'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('manual_final_total_reason_category', $manualFinalTotalAudit?->reason_category) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('manual_final_total_reason_category')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                    <label>
                        Manual final total explanation
                        <input @class(['field-input', 'is-invalid' => $errors->has('manual_final_total_reason')]) name="manual_final_total_reason" type="text" value="{{ old('manual_final_total_reason', $revision?->manual_final_total_reason ?? '') }}">
                        @error('manual_final_total_reason')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                @endif
            </div>

            <label>
                Revision notes
                <textarea name="revision_notes">{{ old('revision_notes', $revision?->notes ?? '') }}</textarea>
            </label>
        </section>

        <section class="panel">
            <div class="page-header">
                <div>
                    <h2 class="section-title">Three-leg route entry</h2>
                    <p class="section-copy">This MVP is manual-first. Enter the miles for each fixed leg and the pricing engine will use those stored values.</p>
                </div>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Leg</th>
                        <th>Pricing rule</th>
                        <th>Manual miles</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($routeLegDefinitions as $index => $definition)
                        @php
                            $routeLeg = $routeLegsBySequence->get($definition['sequence']);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $definition['title'] }}</strong>
                                <div class="muted">{{ $definition['copy'] }}</div>
                            </td>
                            <td>{{ ucfirst($definition['rate_type']) }}</td>
                            <td>
                                <input
                                    @class(['field-input', 'is-invalid' => $errors->has("route_legs.$index.miles")])
                                    name="route_legs[{{ $index }}][miles]"
                                    min="1"
                                    step="1"
                                    type="number"
                                    value="{{ old("route_legs.$index.miles", $routeLeg?->manual_miles ?? $routeLeg?->miles ?? '') }}"
                                    required
                                >
                                @error("route_legs.$index.miles")
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </fieldset>

    @if (! $isEditable)
        <p class="field-help">
            @if (! $isCurrentWorkingRevision)
                Revision history is read-only. Open the current working revision to make changes.
            @else
                Return this quote to draft before saving changes to customer details, route miles, or pricing inputs.
            @endif
        </p>
    @elseif ($canSubmit)
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
    @else
        <p class="field-help">This quote cannot be saved yet.</p>
    @endif
</form>
