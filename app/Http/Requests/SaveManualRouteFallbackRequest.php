<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveManualRouteFallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageQuoteExceptions() ?? false;
    }

    public function rules(): array
    {
        return [
            'reason_category' => ['required', 'string', Rule::in([
                'provider_outage',
                'postcode_ambiguity',
                'provider_response_invalid',
                'disputed_mileage',
                'operational_exception',
            ])],
            'explanation' => ['required', 'string', 'max:2000'],
            'route_legs' => ['required', 'array', 'size:3'],
            'route_legs.*.miles' => ['required', 'integer', 'min:1'],
        ];
    }
}
