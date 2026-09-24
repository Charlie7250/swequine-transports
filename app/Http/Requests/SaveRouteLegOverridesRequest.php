<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveRouteLegOverridesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageQuoteExceptions() ?? false;
    }

    public function rules(): array
    {
        return [
            'overrides' => ['required', 'array', 'min:1'],
            'overrides.*.miles' => ['nullable', 'integer', 'min:1'],
            'overrides.*.reason_category' => ['nullable', 'string', Rule::in([
                'postcode_ambiguity',
                'disputed_mileage',
                'operational_exception',
            ])],
            'overrides.*.explanation' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('overrides', []) as $index => $override) {
                if (! is_array($override) || blank($override['miles'] ?? null)) {
                    continue;
                }

                if (blank($override['reason_category'] ?? null)) {
                    $validator->errors()->add("overrides.{$index}.reason_category", 'Select a reason category for each changed route leg.');
                }

                if (blank($override['explanation'] ?? null)) {
                    $validator->errors()->add("overrides.{$index}.explanation", 'Explain each changed route leg.');
                }
            }
        });
    }
}
