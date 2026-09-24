<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreFuelPriceSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => Str::slug((string) $this->input('display_name'), '_'),
        ]);
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255', 'unique:fuel_price_sources,display_name'],
            'key' => ['required', 'string', 'max:255', 'unique:fuel_price_sources,key'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Enter a name for the fuel source.',
            'display_name.unique' => 'A fuel source with this name already exists.',
            'key.required' => 'Enter a name that produces a usable source key.',
            'key.unique' => 'A fuel source matching this name already exists.',
        ];
    }
}
