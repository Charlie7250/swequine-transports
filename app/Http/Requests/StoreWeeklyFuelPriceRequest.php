<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeeklyFuelPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activate_now' => $this->boolean('activate_now'),
        ]);
    }

    public function rules(): array
    {
        return [
            'week_commencing' => ['required', 'date'],
            'source' => ['required', Rule::exists('fuel_price_sources', 'key')],
            'price_per_litre_inc_vat' => ['required', 'numeric', 'gt:0'],
            'activate_now' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'week_commencing.required' => 'Enter the week commencing date.',
            'source.required' => 'Enter the source for this weekly fuel entry.',
            'price_per_litre_inc_vat.required' => 'Enter the fuel price per litre including VAT.',
        ];
    }
}
