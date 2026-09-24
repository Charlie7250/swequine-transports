<?php

namespace App\Services\LoadingPractice;

use App\Models\Customer;
use App\Models\LoadingPracticeQuote;
use App\Services\Pricing\LoadingPracticePricingCalculator;
use App\Services\Pricing\LoadingPracticePricingContextResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class LoadingPracticeQuoteManager
{
    public function __construct(
        private readonly LoadingPracticePricingCalculator $pricingCalculator,
        private readonly LoadingPracticePricingContextResolver $pricingContextResolver,
    ) {}

    public function create(array $attributes): LoadingPracticeQuote
    {
        return DB::transaction(function () use ($attributes): LoadingPracticeQuote {
            $customer = Customer::query()->create($this->customerAttributes($attributes));
            $quote = LoadingPracticeQuote::query()->create([
                'customer_id' => $customer->id,
                'status' => 'draft',
                'notes' => $attributes['quote_notes'] ?? null,
            ]);

            return $this->priceAndPersist($quote, $attributes, useStoredRateSetting: false);
        });
    }

    public function update(LoadingPracticeQuote $quote, array $attributes): LoadingPracticeQuote
    {
        return DB::transaction(function () use ($quote, $attributes): LoadingPracticeQuote {
            $quote->customer()->update($this->customerAttributes($attributes));
            $quote->forceFill([
                'notes' => $attributes['quote_notes'] ?? null,
            ])->save();

            return $this->priceAndPersist($quote, $attributes, useStoredRateSetting: true);
        });
    }

    private function priceAndPersist(LoadingPracticeQuote $quote, array $attributes, bool $useStoredRateSetting): LoadingPracticeQuote
    {
        $rateSetting = $useStoredRateSetting
            ? $this->pricingContextResolver->resolveRateSettingForUpdate($quote)
            : $this->pricingContextResolver->resolveRateSettingForCreate();

        if ($useStoredRateSetting && $quote->rate_setting_id === null) {
            throw ValidationException::withMessages([
                'pricing' => 'Loading-practice quotes must keep their stored rate setting to remain auditable.',
            ]);
        }

        if ($rateSetting === null) {
            throw ValidationException::withMessages([
                'pricing' => $useStoredRateSetting
                    ? 'Loading-practice quotes must keep their stored rate setting to remain auditable.'
                    : 'Loading practice pricing requires one active rate setting.',
            ]);
        }

        try {
            $pricedQuote = $this->pricingCalculator->calculate(array_merge($attributes, [
                'loading_practice_within_15_miles_price' => $rateSetting->loading_practice_within_15_miles_price,
                'loading_practice_within_25_miles_price' => $rateSetting->loading_practice_within_25_miles_price,
                'loading_practice_within_50_miles_price' => $rateSetting->loading_practice_within_50_miles_price,
                'loading_practice_on_site_hourly_rate' => $rateSetting->loading_practice_on_site_hourly_rate,
                'loading_practice_livery_day_rate' => $rateSetting->loading_practice_livery_day_rate,
                'loading_practice_livery_week_rate' => $rateSetting->loading_practice_livery_week_rate,
                'loading_practice_livery_fortnight_rate' => $rateSetting->loading_practice_livery_fortnight_rate,
            ]));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'pricing' => $exception->getMessage(),
            ]);
        }

        $quote->forceFill([
            'rate_setting_id' => $quote->rate_setting_id ?? $rateSetting->id,
            'distance_band' => $pricedQuote['distance_band'],
            'travel_miles' => $pricedQuote['travel_miles'],
            'package_price' => $pricedQuote['package_price'],
            'on_site_hours' => $pricedQuote['on_site']['hours'],
            'on_site_hourly_rate' => $pricedQuote['on_site']['hourly_rate'],
            'handling_livery_period' => $pricedQuote['handling_livery']['period'],
            'handling_livery_quantity' => $pricedQuote['handling_livery']['quantity'],
            'handling_livery_rate' => $pricedQuote['handling_livery']['rate'],
            'is_poa' => $pricedQuote['is_poa'],
            'engine_total' => $pricedQuote['engine_total'],
            'final_total' => $pricedQuote['final_total'],
            'manual_final_total_reason' => $attributes['manual_final_total_reason'] ?? null,
            'calculation_explanation' => $pricedQuote,
        ])->save();

        return $quote->fresh(['customer', 'rateSetting']);
    }

    private function customerAttributes(array $attributes): array
    {
        return [
            'name' => $attributes['customer_name'],
            'contact_name' => $attributes['customer_contact_name'] ?? null,
            'email' => $attributes['customer_email'] ?? null,
            'phone' => $attributes['customer_phone'] ?? null,
            'postcode' => $attributes['customer_postcode'] ?? null,
            'notes' => $attributes['customer_notes'] ?? null,
        ];
    }
}
