<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectTransportEnquiryRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageQuoteExceptions() ?? false;
    }

    public function rules(): array
    {
        return [
            'pickup_postcode' => ['required', 'string', 'max:16'],
            'dropoff_postcode' => ['required', 'string', 'max:16'],
        ];
    }
}
