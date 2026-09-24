<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

class LoadingPracticePricingCalculator
{
    public const DISTANCE_BAND_WITHIN_15_MILES = 'within_15_miles';

    public const DISTANCE_BAND_WITHIN_25_MILES = 'within_25_miles';

    public const DISTANCE_BAND_WITHIN_50_MILES = 'within_50_miles';

    public const DISTANCE_BAND_OVER_50_MILES = 'over_50_miles';

    public function calculate(array $input): array
    {
        $travelMiles = $this->normaliseTravelMiles($input['travel_miles'] ?? null);
        $distanceBand = $this->resolveDistanceBand($travelMiles);
        $isPoa = $distanceBand === self::DISTANCE_BAND_OVER_50_MILES;
        $onSiteHours = $this->normaliseOptionalDecimal($input['on_site_hours'] ?? null, 'on_site_hours');
        $manualFinalTotal = $this->normaliseOptionalDecimal($input['manual_final_total'] ?? null, 'manual_final_total');
        $handlingLivery = $this->resolveHandlingLivery($input);
        $overrides = $this->buildOverrides($manualFinalTotal);

        $packagePrice = $isPoa
            ? null
            : $this->resolveConfiguredAmount($input, match ($distanceBand) {
                self::DISTANCE_BAND_WITHIN_15_MILES => 'loading_practice_within_15_miles_price',
                self::DISTANCE_BAND_WITHIN_25_MILES => 'loading_practice_within_25_miles_price',
                self::DISTANCE_BAND_WITHIN_50_MILES => 'loading_practice_within_50_miles_price',
            });

        $onSiteHourlyRate = $this->resolveConfiguredAmount($input, 'loading_practice_on_site_hourly_rate');
        $onSiteAmount = $this->shouldApplyOnSiteCharge($distanceBand)
            ? $this->formatMoney(($onSiteHours ?? 0.0) * $onSiteHourlyRate)
            : '0.00';

        $engineTotal = $isPoa
            ? null
            : $this->formatMoney(
                (float) $packagePrice
                + (float) $onSiteAmount
                + (float) $handlingLivery['amount'],
            );

        $finalTotal = $manualFinalTotal !== null
            ? $this->formatMoney($manualFinalTotal)
            : $engineTotal;

        return [
            'travel_miles' => $travelMiles,
            'distance_band' => $distanceBand,
            'package_price' => $packagePrice !== null ? $this->formatMoney($packagePrice) : null,
            'on_site' => [
                'hours' => $onSiteHours !== null ? $this->formatHours($onSiteHours) : null,
                'hourly_rate' => $this->formatMoney($onSiteHourlyRate),
                'amount' => $onSiteAmount,
                'is_applied' => $this->shouldApplyOnSiteCharge($distanceBand),
            ],
            'handling_livery' => $handlingLivery,
            'is_poa' => $isPoa,
            'engine_total' => $engineTotal,
            'final_total' => $finalTotal,
            'overrides' => $overrides,
        ];
    }

    private function resolveDistanceBand(int $travelMiles): string
    {
        return match (true) {
            $travelMiles <= 15 => self::DISTANCE_BAND_WITHIN_15_MILES,
            $travelMiles <= 25 => self::DISTANCE_BAND_WITHIN_25_MILES,
            $travelMiles <= 50 => self::DISTANCE_BAND_WITHIN_50_MILES,
            default => self::DISTANCE_BAND_OVER_50_MILES,
        };
    }

    private function shouldApplyOnSiteCharge(string $distanceBand): bool
    {
        return in_array($distanceBand, [
            self::DISTANCE_BAND_WITHIN_25_MILES,
            self::DISTANCE_BAND_WITHIN_50_MILES,
        ], true);
    }

    private function resolveHandlingLivery(array $input): array
    {
        $period = $input['handling_livery_period'] ?? null;
        $quantity = $input['handling_livery_quantity'] ?? null;

        if ($period === null || $period === '') {
            return [
                'period' => null,
                'quantity' => null,
                'rate' => null,
                'amount' => '0.00',
            ];
        }

        if (! in_array($period, ['day', 'week', 'fortnight'], true)) {
            throw new InvalidArgumentException('The [handling_livery_period] pricing input is invalid.');
        }

        if (! is_numeric($quantity) || (int) $quantity < 1) {
            throw new InvalidArgumentException('The [handling_livery_quantity] pricing input must be at least 1.');
        }

        $rateField = match ($period) {
            'day' => 'loading_practice_livery_day_rate',
            'week' => 'loading_practice_livery_week_rate',
            'fortnight' => 'loading_practice_livery_fortnight_rate',
        };

        $rate = $this->resolveConfiguredAmount($input, $rateField);

        return [
            'period' => $period,
            'quantity' => (int) $quantity,
            'rate' => $this->formatMoney($rate),
            'amount' => $this->formatMoney((int) $quantity * $rate),
        ];
    }

    private function buildOverrides(?float $manualFinalTotal): array
    {
        if ($manualFinalTotal === null) {
            return [];
        }

        return [[
            'type' => 'manual_final_total',
            'amount' => $this->formatMoney($manualFinalTotal),
        ]];
    }

    private function normaliseTravelMiles(mixed $value): int
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException('The [travel_miles] pricing input must be numeric.');
        }

        $travelMiles = (int) round((float) $value);

        if ($travelMiles < 1) {
            throw new InvalidArgumentException('The [travel_miles] pricing input must be greater than zero.');
        }

        return $travelMiles;
    }

    private function normaliseOptionalDecimal(mixed $value, string $field): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("The [{$field}] pricing input must be numeric.");
        }

        $decimal = (float) $value;

        if ($decimal < 0) {
            throw new InvalidArgumentException("The [{$field}] pricing input must not be negative.");
        }

        if ($field === 'manual_final_total' && $decimal <= 0) {
            throw new InvalidArgumentException('The [manual_final_total] pricing input must be greater than zero.');
        }

        return $decimal;
    }

    private function resolveConfiguredAmount(array $input, string $field): float
    {
        $value = $input[$field] ?? null;

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("The [{$field}] pricing input must be numeric.");
        }

        $amount = (float) $value;

        if ($amount < 0) {
            throw new InvalidArgumentException("The [{$field}] pricing input must not be negative.");
        }

        return $amount;
    }

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function formatHours(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
