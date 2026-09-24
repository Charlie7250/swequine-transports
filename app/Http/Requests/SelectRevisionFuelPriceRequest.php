<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectRevisionFuelPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weekly_fuel_price_id' => ['required', 'integer', Rule::exists('weekly_fuel_prices', 'id')],
        ];
    }
}
