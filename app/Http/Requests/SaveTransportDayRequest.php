<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveTransportDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'run_date' => ['required', 'date'],
            'name' => ['nullable', 'string', 'max:255'],
            'depot_postcode' => ['nullable', 'string', 'max:16'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'run_date.required' => 'Enter the date for this transport day.',
        ];
    }
}
