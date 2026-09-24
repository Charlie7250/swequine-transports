<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveLoadingPracticeQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_contact_name' => $this->filled('customer_contact_name') ? $this->input('customer_contact_name') : null,
            'customer_email' => $this->filled('customer_email') ? $this->input('customer_email') : null,
            'customer_phone' => $this->filled('customer_phone') ? $this->input('customer_phone') : null,
            'customer_postcode' => $this->filled('customer_postcode') ? $this->input('customer_postcode') : null,
            'customer_notes' => $this->filled('customer_notes') ? $this->input('customer_notes') : null,
            'on_site_hours' => $this->filled('on_site_hours') ? $this->input('on_site_hours') : null,
            'handling_livery_period' => $this->filled('handling_livery_period') ? $this->input('handling_livery_period') : null,
            'handling_livery_quantity' => $this->filled('handling_livery_quantity') ? $this->input('handling_livery_quantity') : null,
            'manual_final_total' => $this->filled('manual_final_total') ? $this->input('manual_final_total') : null,
            'manual_final_total_reason' => $this->filled('manual_final_total_reason') ? $this->input('manual_final_total_reason') : null,
            'quote_notes' => $this->filled('quote_notes') ? $this->input('quote_notes') : null,
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
            'travel_miles' => ['required', 'integer', 'min:1'],
            'on_site_hours' => ['nullable', 'numeric', 'gte:0'],
            'handling_livery_period' => ['nullable', 'in:day,week,fortnight', 'required_with:handling_livery_quantity'],
            'handling_livery_quantity' => ['nullable', 'integer', 'min:1', 'required_with:handling_livery_period'],
            'manual_final_total' => ['nullable', 'numeric', 'gt:0', 'required_with:manual_final_total_reason'],
            'manual_final_total_reason' => ['nullable', 'string', 'max:255'],
            'quote_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Enter the customer name.',
            'travel_miles.required' => 'Enter the travel miles for this loading-practice quote.',
            'travel_miles.min' => 'Travel miles must be at least 1.',
            'handling_livery_period.required_with' => 'Choose the handling and loading livery period.',
            'handling_livery_quantity.required_with' => 'Enter the handling and loading livery quantity.',
            'manual_final_total.required_with' => 'Enter a manual final total when overriding the quoted amount.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $travelMiles = $this->integer('travel_miles');

            if ($travelMiles <= 50 || $this->filled('manual_final_total')) {
                return;
            }

            $validator->errors()->add('manual_final_total', 'Enter a manual final total before saving a loading-practice quote over 50 miles.');
        });
    }
}
