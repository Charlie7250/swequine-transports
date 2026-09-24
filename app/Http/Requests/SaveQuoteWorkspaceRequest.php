<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveQuoteWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->hasManualFinalTotalInput()) {
            return true;
        }

        return $this->user()?->canManageQuoteExceptions() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'manual_final_total' => $this->filled('manual_final_total')
                ? $this->input('manual_final_total')
                : null,
            'manual_final_total_reason' => $this->filled('manual_final_total_reason')
                ? $this->input('manual_final_total_reason')
                : null,
            'manual_final_total_reason_category' => $this->filled('manual_final_total_reason_category')
                ? $this->input('manual_final_total_reason_category')
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_contact_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'customer_postcode' => ['nullable', 'string', 'max:16'],
            'customer_notes' => ['nullable', 'string'],
            'horse_count' => ['required', 'integer', 'min:1', 'max:2'],
            'pickup_postcode' => ['required', 'string', 'max:16'],
            'dropoff_postcode' => ['required', 'string', 'max:16'],
            'revision_notes' => ['nullable', 'string'],
            'manual_final_total' => [
                'nullable',
                'numeric',
                'gt:0',
                'regex:/^\d+\.\d{2}$/',
                'required_with:manual_final_total_reason,manual_final_total_reason_category',
            ],
            'manual_final_total_reason_category' => [
                'nullable',
                'required_with:manual_final_total,manual_final_total_reason',
                'string',
                Rule::in(['commercial_adjustment', 'customer_agreement', 'goodwill_adjustment']),
            ],
            'manual_final_total_reason' => [
                'nullable',
                'required_with:manual_final_total,manual_final_total_reason_category',
                'string',
                'max:2000',
            ],
            'route_legs' => ['required', 'array', 'size:3'],
            'route_legs.0' => ['required', 'array'],
            'route_legs.1' => ['required', 'array'],
            'route_legs.2' => ['required', 'array'],
            'route_legs.*.miles' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Enter the customer name.',
            'horse_count.required' => 'Enter the number of horses for this quote.',
            'horse_count.min' => 'Enter at least one horse for this quote.',
            'horse_count.max' => 'Automatic transport pricing supports one or two horses. Counts above two require manual review.',
            'pickup_postcode.required' => 'Enter the pickup postcode.',
            'dropoff_postcode.required' => 'Enter the drop-off postcode.',
            'manual_final_total.required_with' => 'Enter a manual final total when overriding the quoted amount.',
            'manual_final_total_reason_category.required_with' => 'Select a reason category for the manual final total.',
            'manual_final_total_reason.required_with' => 'Explain the manual final total.',
            'route_legs.required' => 'Enter miles for each of the three transport legs.',
            'route_legs.size' => 'Keep all three transport legs in place before saving this quote.',
            'route_legs.0.required' => 'Enter the depot to pickup miles.',
            'route_legs.1.required' => 'Enter the pickup to drop-off miles.',
            'route_legs.2.required' => 'Enter the drop-off to depot miles.',
            'route_legs.*.miles.required' => 'Enter the miles for this transport leg.',
            'route_legs.*.miles.gt' => 'Miles must be greater than zero.',
        ];
    }

    private function hasManualFinalTotalInput(): bool
    {
        return $this->filled('manual_final_total')
            || $this->filled('manual_final_total_reason_category')
            || $this->filled('manual_final_total_reason');
    }
}
