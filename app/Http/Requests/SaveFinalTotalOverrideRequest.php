<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFinalTotalOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageQuoteExceptions() ?? false;
    }

    public function rules(): array
    {
        return [
            'final_total' => ['required', 'numeric', 'gt:0', 'regex:/^\d+\.\d{2}$/'],
            'reason_category' => ['required', 'string', Rule::in([
                'commercial_adjustment',
                'customer_agreement',
                'goodwill_adjustment',
            ])],
            'explanation' => ['required', 'string', 'max:2000'],
        ];
    }
}
