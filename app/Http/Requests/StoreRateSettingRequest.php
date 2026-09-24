<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRateSettingRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'depot_postcode' => ['required', 'string', 'max:16'],
            'miles_per_gallon' => ['required', 'numeric', 'gt:0'],
            'litres_per_gallon' => ['required', 'numeric', 'gt:0'],
            'maintenance_per_mile' => ['required', 'numeric', 'gte:0'],
            'unloaded_add_on_per_mile' => ['required', 'numeric', 'gte:0'],
            'loaded_add_on_per_mile' => ['required', 'numeric', 'gte:0'],
            'one_horse_multiplier' => ['required', 'numeric', 'gt:0'],
            'shared_load_percentage' => ['required', 'numeric', 'gt:0', 'lte:1'],
            'two_horse_multiplier' => ['required', 'numeric', 'gt:0'],
            'loading_practice_within_15_miles_price' => ['required', 'numeric', 'gte:0'],
            'loading_practice_within_25_miles_price' => ['required', 'numeric', 'gte:0'],
            'loading_practice_within_50_miles_price' => ['required', 'numeric', 'gte:0'],
            'loading_practice_on_site_hourly_rate' => ['required', 'numeric', 'gte:0'],
            'loading_practice_livery_day_rate' => ['required', 'numeric', 'gte:0'],
            'loading_practice_livery_week_rate' => ['required', 'numeric', 'gte:0'],
            'loading_practice_livery_fortnight_rate' => ['required', 'numeric', 'gte:0'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'activate_now' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter a name for this rate setting.',
            'depot_postcode.required' => 'Enter the depot postcode.',
            'effective_from.required' => 'Enter the date these rate settings take effect.',
            'shared_load_percentage.lte' => 'Enter the shared-load percentage as a decimal between 0 and 1.',
        ];
    }
}
