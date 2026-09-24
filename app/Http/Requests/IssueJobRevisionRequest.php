<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueJobRevisionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'issue_confirmation' => ['required', 'accepted'],
            'confirmed_revision_id' => [
                'required',
                'integer',
                Rule::in([(int) $this->route('revision')->id]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'issue_confirmation.accepted' => 'Confirm that this reviewed revision is the quote to issue.',
            'confirmed_revision_id.in' => 'Confirm the exact revision shown before issuing the quote.',
        ];
    }
}
