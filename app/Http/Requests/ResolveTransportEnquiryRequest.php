<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ResolveTransportEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_to_be_arranged' => $this->boolean('date_to_be_arranged'),
            'special_constraints_acknowledged' => $this->boolean('special_constraints_acknowledged'),
        ]);
    }

    public function rules(): array
    {
        return [
            'submission_token' => ['required', 'uuid'],
            'source' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'pickup_postcode' => ['required', 'string', 'max:16'],
            'dropoff_postcode' => ['required', 'string', 'max:16'],
            'horse_count' => ['required', 'integer', 'min:1', 'max:255'],
            'requested_date' => ['nullable', 'date'],
            'date_to_be_arranged' => ['boolean'],
            'special_constraints' => ['nullable', 'string'],
            'special_constraints_acknowledged' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'submission_token.required' => 'This route submission could not be verified. Return to the transport enquiry and try again.',
            'submission_token.uuid' => 'This route submission could not be verified. Return to the transport enquiry and try again.',
            'source.required' => 'Select the enquiry source before issuing a quote.',
            'customer_name.required' => "Enter the customer's name before issuing a quote.",
            'pickup_postcode.required' => 'Enter the pickup postcode to calculate the depot-to-pickup and pickup-to-drop-off legs.',
            'dropoff_postcode.required' => 'Enter the drop-off postcode to calculate the pickup-to-drop-off and drop-off-to-depot legs.',
            'horse_count.required' => 'Enter the number of horses for this transport quote.',
            'horse_count.min' => 'Enter the number of horses for this transport quote.',
            'special_constraints_acknowledged.accepted' => 'Confirm whether any special transport constraints are known before issuing a quote.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (blank($this->input('email')) && blank($this->input('phone'))) {
                $validator->errors()->add('email', 'Add at least one way to contact the customer before issuing a quote.');
            }

            if (blank($this->input('requested_date')) && ! $this->boolean('date_to_be_arranged')) {
                $validator->errors()->add('requested_date', 'Enter the requested date or select date to be arranged before issuing a quote.');
            }
        });
    }
}
