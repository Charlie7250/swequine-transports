<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

class DeterministicPricingCalculator
{
    public const STANDARD_SINGLE_QUOTE_LEGS = [
        ['label' => 'depot_to_pickup', 'rate_type' => 'unloaded'],
        ['label' => 'pickup_to_dropoff', 'rate_type' => 'loaded'],
        ['label' => 'dropoff_to_depot', 'rate_type' => 'unloaded'],
    ];

    public function calculate(array $input): array
    {
        $manualFinalTotal = $this->validateCommonInput($input);
        $horsePricing = $this->resolveHorsePricing($input);
        $resolvedRates = $this->resolveRates($input, $horsePricing);
        $pricedLegs = $this->priceStandardLegs($input['legs'], $resolvedRates);

        return $this->buildStandardPayload($input, $resolvedRates, $pricedLegs, $horsePricing, $manualFinalTotal);
    }

    public function calculateSharedAllocation(array $input): array
    {
        $manualFinalTotal = $this->validateCommonInput($input);
        $horsePricing = $this->resolveHorsePricing($input);
        $resolvedRates = $this->resolveRates($input, $horsePricing);
        $sharedLoadPercentage = $this->normaliseSharedLoadPercentage($input['shared_load_percentage'] ?? null);
        $pricedLegs = $this->priceSharedAllocationLegs($input['allocation_legs'] ?? null, $resolvedRates, $sharedLoadPercentage);
        $basePayload = $this->buildBasePayload($input, $resolvedRates, $pricedLegs, $horsePricing, $manualFinalTotal);

        return array_merge($basePayload, [
            'shared_load' => [
                'type' => 'shared_run',
                'shared_load_percentage' => $this->formatRate($sharedLoadPercentage),
                'full_charge_miles' => (string) $this->sumSharedMiles($pricedLegs, 'full_miles'),
                'split_charge_miles' => (string) $this->sumSharedMiles($pricedLegs, 'split_miles'),
                'allocation_legs' => $pricedLegs,
            ],
        ]);
    }

    private function buildStandardPayload(
        array $input,
        array $resolvedRates,
        array $pricedLegs,
        array $horsePricing,
        null|int|float|string $manualFinalTotal,
    ): array {
        return $this->buildBasePayload($input, $resolvedRates, $pricedLegs, $horsePricing, $manualFinalTotal);
    }

    private function buildBasePayload(
        array $input,
        array $resolvedRates,
        array $pricedLegs,
        array $horsePricing,
        null|int|float|string $manualFinalTotal,
    ): array {
        $engineTotal = $this->sumLegAmounts($pricedLegs);
        $overrides = $this->buildOverrides($manualFinalTotal);
        $finalTotal = $manualFinalTotal === null ? $engineTotal : $this->formatMoney((float) $manualFinalTotal);
        $rateInputs = [
            'miles_per_gallon' => $this->formatRate((float) $input['miles_per_gallon'], 4),
            'litres_per_gallon' => $this->formatRate((float) $input['litres_per_gallon'], 4),
            'maintenance_per_mile' => $this->formatRate((float) $input['maintenance_per_mile']),
            'unloaded_add_on_per_mile' => $this->formatRate((float) $input['unloaded_add_on_per_mile']),
            'loaded_add_on_per_mile' => $this->formatRate((float) $input['loaded_add_on_per_mile']),
            'one_horse_multiplier' => $this->formatRate((float) $input['one_horse_multiplier']),
            'two_horse_multiplier' => $this->formatRate((float) $input['two_horse_multiplier']),
        ];

        if (array_key_exists('shared_load_percentage', $input) && trim((string) $input['shared_load_percentage']) !== '') {
            $rateInputs['shared_load_percentage'] = $this->formatRate((float) $input['shared_load_percentage']);
        }

        return [
            'fuel_context' => [
                'week_commencing' => $input['week_commencing'],
                'source' => $input['fuel_source'],
                'price_per_litre_inc_vat' => $this->formatRate((float) $input['fuel_price_per_litre_inc_vat'], 4),
            ],
            'rate_inputs' => $rateInputs,
            'resolved_rates' => $resolvedRates,
            'horse_count' => $horsePricing,
            'legs' => $pricedLegs,
            'extras' => [],
            'overrides' => $overrides,
            'engine_total' => $engineTotal,
            'final_total' => $finalTotal,
        ];
    }

    private function validateCommonInput(array $input): null|int|float|string
    {
        $this->requireValue($input, 'week_commencing');
        $this->requireValue($input, 'fuel_source');
        $this->requireNumericValue($input, 'fuel_price_per_litre_inc_vat', mustBePositive: true);
        $this->requireNumericValue($input, 'miles_per_gallon', mustBePositive: true);
        $this->requireNumericValue($input, 'litres_per_gallon', mustBePositive: true);
        $this->requireNumericValue($input, 'maintenance_per_mile', mustBeNonNegative: true);
        $this->requireNumericValue($input, 'unloaded_add_on_per_mile', mustBeNonNegative: true);
        $this->requireNumericValue($input, 'loaded_add_on_per_mile', mustBeNonNegative: true);

        return $this->normaliseManualFinalTotal($input['manual_final_total'] ?? null);
    }

    private function resolveHorsePricing(array $input): array
    {
        $horseCount = $this->normaliseSupportedHorseCount($input['horse_count'] ?? null);
        $multiplierKey = $horseCount === 1 ? 'one_horse_multiplier' : 'two_horse_multiplier';
        $this->requireNumericValue($input, $multiplierKey, mustBePositive: true);

        return [
            'count' => $horseCount,
            'multiplier' => $this->formatRate((float) $input[$multiplierKey]),
        ];
    }

    private function resolveRates(array $input, array $horsePricing): array
    {
        $fuelPrice = (float) $input['fuel_price_per_litre_inc_vat'];
        $litresPerGallon = (float) $input['litres_per_gallon'];
        $milesPerGallon = (float) $input['miles_per_gallon'];
        $maintenancePerMile = (float) $input['maintenance_per_mile'];
        $unloadedAddOn = (float) $input['unloaded_add_on_per_mile'];
        $loadedAddOn = (float) $input['loaded_add_on_per_mile'];

        $baseCostPerMile = round((($fuelPrice * $litresPerGallon) / $milesPerGallon) + $maintenancePerMile, 6);

        $baseLoadedRate = round($baseCostPerMile + $loadedAddOn, 6);
        $loadedRate = round($baseLoadedRate * (float) $horsePricing['multiplier'], 6);

        return [
            'base_cost_per_mile' => $this->formatRate($baseCostPerMile),
            'unloaded_rate_per_mile' => $this->formatRate(round($baseCostPerMile + $unloadedAddOn, 6)),
            'base_loaded_rate_per_mile' => $this->formatRate($baseLoadedRate),
            'loaded_rate_per_mile' => $this->formatRate($loadedRate),
        ];
    }

    private function normaliseSupportedHorseCount(mixed $value): int
    {
        if ($value === null || trim((string) $value) === '') {
            throw new InvalidArgumentException('The [horse_count] pricing input is required.');
        }

        if (! is_numeric($value) || floor((float) $value) !== (float) $value) {
            throw new InvalidArgumentException('The [horse_count] pricing input must be a whole number.');
        }

        if (! in_array((int) $value, [1, 2], true)) {
            throw new InvalidArgumentException('Automatic transport pricing supports one or two horses. Counts above two require manual review.');
        }

        return (int) $value;
    }

    private function priceStandardLegs(mixed $legs, array $resolvedRates): array
    {
        $validatedLegs = $this->validateStandardLegs($legs);

        return array_map(function (array $leg) use ($resolvedRates): array {
            $miles = (int) round((float) $leg['miles']);
            $rateKey = $this->resolveLegRateKey($leg['rate_type']);
            $ratePerMile = (float) $resolvedRates[$rateKey];
            $amount = round($miles * $ratePerMile, 2);

            return [
                'label' => $leg['label'],
                'miles' => $miles,
                'rate_type' => $leg['rate_type'],
                'rate_per_mile' => $this->formatRate($ratePerMile),
                'amount' => $this->formatMoney($amount),
            ];
        }, $validatedLegs);
    }

    private function priceSharedAllocationLegs(mixed $legs, array $resolvedRates, float $sharedLoadPercentage): array
    {
        $validatedLegs = $this->validateSharedAllocationLegs($legs);

        return array_map(function (array $leg) use ($resolvedRates, $sharedLoadPercentage): array {
            $rateKey = $this->resolveLegRateKey($leg['rate_type']);
            $ratePerMile = (float) $resolvedRates[$rateKey];
            $fullAmount = round($leg['full_miles'] * $ratePerMile, 2);
            $splitAmount = $leg['split_miles'] === 0
                ? 0.0
                : $this->calculateSplitAmount($leg, $ratePerMile, $sharedLoadPercentage);
            $amount = round($fullAmount + $splitAmount, 2);
            $appliedSharedLoadPercentage = $leg['rate_type'] === 'loaded' && $leg['split_miles'] > 0
                ? $this->formatRate($sharedLoadPercentage)
                : null;

            return [
                'label' => $leg['label'],
                'route_miles' => $leg['route_miles'],
                'rate_type' => $leg['rate_type'],
                'rate_per_mile' => $this->formatRate($ratePerMile),
                'full_miles' => $leg['full_miles'],
                'full_amount' => $this->formatMoney($fullAmount),
                'split_miles' => $leg['split_miles'],
                'split_divisor' => $leg['split_divisor'],
                'split_amount' => $this->formatMoney($splitAmount),
                'shared_load_percentage' => $appliedSharedLoadPercentage,
                'chargeable_miles' => $leg['full_miles'] + $leg['split_miles'],
                'amount' => $this->formatMoney($amount),
                'reason' => $leg['reason'],
            ];
        }, $validatedLegs);
    }

    private function validateStandardLegs(mixed $legs): array
    {
        if (! is_array($legs) || count($legs) !== count(self::STANDARD_SINGLE_QUOTE_LEGS)) {
            throw new InvalidArgumentException('Standard single quotes require exactly three route legs: depot_to_pickup, pickup_to_dropoff, and dropoff_to_depot.');
        }

        $validatedLegs = [];

        foreach (self::STANDARD_SINGLE_QUOTE_LEGS as $index => $definition) {
            $leg = $legs[$index] ?? null;

            if (! is_array($leg) || ($leg['label'] ?? null) !== $definition['label']) {
                throw new InvalidArgumentException('Standard single quotes require exactly three route legs: depot_to_pickup, pickup_to_dropoff, and dropoff_to_depot.');
            }

            $this->validateStandardMiles($leg, $index);
            $this->validateRateType($leg, $index, $definition['rate_type']);

            $validatedLegs[] = $leg;
        }

        return $validatedLegs;
    }

    private function validateSharedAllocationLegs(mixed $legs): array
    {
        if (! is_array($legs) || count($legs) !== count(self::STANDARD_SINGLE_QUOTE_LEGS)) {
            throw new InvalidArgumentException('Shared allocations require exactly three route legs: depot_to_pickup, pickup_to_dropoff, and dropoff_to_depot.');
        }

        $validatedLegs = [];
        $chargeableMiles = 0;

        foreach (self::STANDARD_SINGLE_QUOTE_LEGS as $index => $definition) {
            $leg = $legs[$index] ?? null;

            if (! is_array($leg) || ($leg['label'] ?? null) !== $definition['label']) {
                throw new InvalidArgumentException('Shared allocations require exactly three route legs: depot_to_pickup, pickup_to_dropoff, and dropoff_to_depot.');
            }

            $routeMiles = $this->normaliseNonNegativeInteger($leg['route_miles'] ?? null, "allocation_legs.{$index}.route_miles");
            $fullMiles = $this->normaliseNonNegativeInteger($leg['full_miles'] ?? null, "allocation_legs.{$index}.full_miles");
            $splitMiles = $this->normaliseNonNegativeInteger($leg['split_miles'] ?? null, "allocation_legs.{$index}.split_miles");

            if ($routeMiles === 0) {
                throw new InvalidArgumentException("The [allocation_legs.{$index}.route_miles] pricing input must be greater than zero.");
            }

            if ($fullMiles + $splitMiles > $routeMiles) {
                throw new InvalidArgumentException("The [allocation_legs.{$index}] shared allocation cannot charge more than the recorded route miles.");
            }

            $splitDivisor = $this->normaliseSplitDivisor($leg['split_divisor'] ?? null, $splitMiles, $index);
            $reason = $this->normaliseReason($leg['reason'] ?? null, $index);

            $this->validateRateType($leg, $index, $definition['rate_type']);

            $chargeableMiles += $fullMiles + $splitMiles;

            $validatedLegs[] = [
                'label' => $leg['label'],
                'route_miles' => $routeMiles,
                'rate_type' => $leg['rate_type'],
                'full_miles' => $fullMiles,
                'split_miles' => $splitMiles,
                'split_divisor' => $splitDivisor,
                'reason' => $reason,
            ];
        }

        if ($chargeableMiles === 0) {
            throw new InvalidArgumentException('Shared allocations require at least one chargeable mile.');
        }

        return $validatedLegs;
    }

    private function validateStandardMiles(array $leg, int $index): void
    {
        if (! array_key_exists('miles', $leg) || trim((string) $leg['miles']) === '') {
            throw new InvalidArgumentException("The [legs.{$index}.miles] pricing input is required.");
        }

        if (! is_numeric($leg['miles'])) {
            throw new InvalidArgumentException("The [legs.{$index}.miles] pricing input must be numeric.");
        }

        if ((float) $leg['miles'] <= 0) {
            throw new InvalidArgumentException("The [legs.{$index}.miles] pricing input must be greater than zero.");
        }
    }

    private function validateRateType(array $leg, int $index, string $expectedRateType): void
    {
        if (! array_key_exists('rate_type', $leg) || trim((string) $leg['rate_type']) === '') {
            throw new InvalidArgumentException("The [legs.{$index}.rate_type] pricing input is required.");
        }

        $this->resolveLegRateKey($leg['rate_type']);

        if ($leg['rate_type'] !== $expectedRateType) {
            throw new InvalidArgumentException('Standard single quotes require exactly three route legs: depot_to_pickup, pickup_to_dropoff, and dropoff_to_depot.');
        }
    }

    private function normaliseManualFinalTotal(null|int|float|string $manualFinalTotal): null|int|float|string
    {
        if ($manualFinalTotal === null) {
            return null;
        }

        if (trim((string) $manualFinalTotal) === '') {
            return null;
        }

        if (! is_numeric($manualFinalTotal)) {
            throw new InvalidArgumentException('The [manual_final_total] pricing input must be numeric.');
        }

        if ((float) $manualFinalTotal <= 0) {
            throw new InvalidArgumentException('The [manual_final_total] pricing input must be greater than zero.');
        }

        return $manualFinalTotal;
    }

    private function normaliseNonNegativeInteger(mixed $value, string $key): int
    {
        if ($value === null || trim((string) $value) === '') {
            throw new InvalidArgumentException("The [{$key}] pricing input is required.");
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("The [{$key}] pricing input must be numeric.");
        }

        if ((float) $value < 0) {
            throw new InvalidArgumentException("The [{$key}] pricing input must be zero or greater.");
        }

        if (floor((float) $value) !== (float) $value) {
            throw new InvalidArgumentException("The [{$key}] pricing input must be a whole number.");
        }

        return (int) $value;
    }

    private function normaliseSplitDivisor(mixed $value, int $splitMiles, int $index): ?int
    {
        if ($splitMiles === 0) {
            return null;
        }

        if ($value === null || trim((string) $value) === '') {
            throw new InvalidArgumentException("The [allocation_legs.{$index}.split_divisor] pricing input is required.");
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("The [allocation_legs.{$index}.split_divisor] pricing input must be numeric.");
        }

        if ((int) $value < 2) {
            throw new InvalidArgumentException("The [allocation_legs.{$index}.split_divisor] pricing input must be at least 2.");
        }

        return (int) $value;
    }

    private function normaliseReason(mixed $value, int $index): string
    {
        if ($value === null || trim((string) $value) === '') {
            throw new InvalidArgumentException("The [allocation_legs.{$index}.reason] pricing input is required.");
        }

        return trim((string) $value);
    }

    private function normaliseSharedLoadPercentage(mixed $value): float
    {
        if ($value === null || trim((string) $value) === '') {
            throw new InvalidArgumentException('The [shared_load_percentage] pricing input is required.');
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('The [shared_load_percentage] pricing input must be numeric.');
        }

        if ((float) $value <= 0) {
            throw new InvalidArgumentException('The [shared_load_percentage] pricing input must be greater than zero.');
        }

        if ((float) $value > 1) {
            throw new InvalidArgumentException('The [shared_load_percentage] pricing input must not be greater than 1.');
        }

        return (float) $value;
    }

    private function calculateSplitAmount(array $leg, float $ratePerMile, float $sharedLoadPercentage): float
    {
        if ($leg['rate_type'] === 'loaded') {
            return round($leg['split_miles'] * $ratePerMile * $sharedLoadPercentage, 2);
        }

        return round(($leg['split_miles'] * $ratePerMile) / $leg['split_divisor'], 2);
    }

    private function requireValue(array $input, string $key): void
    {
        if (! array_key_exists($key, $input) || trim((string) $input[$key]) === '') {
            throw new InvalidArgumentException("The [{$key}] pricing input is required.");
        }
    }

    private function requireNumericValue(
        array $input,
        string $key,
        bool $mustBePositive = false,
        bool $mustBeNonNegative = false,
    ): void {
        $this->requireValue($input, $key);

        if (! is_numeric($input[$key])) {
            throw new InvalidArgumentException("The [{$key}] pricing input must be numeric.");
        }

        if ($mustBePositive && (float) $input[$key] <= 0) {
            throw new InvalidArgumentException("The [{$key}] pricing input must be greater than zero.");
        }

        if ($mustBeNonNegative && (float) $input[$key] < 0) {
            throw new InvalidArgumentException("The [{$key}] pricing input must be zero or greater.");
        }
    }

    private function resolveLegRateKey(string $rateType): string
    {
        return match ($rateType) {
            'loaded' => 'loaded_rate_per_mile',
            'unloaded' => 'unloaded_rate_per_mile',
            default => throw new InvalidArgumentException("Unsupported leg rate type [{$rateType}]."),
        };
    }

    private function buildOverrides(null|int|float|string $manualFinalTotal): array
    {
        if ($manualFinalTotal === null) {
            return [];
        }

        return [[
            'type' => 'manual_final_total',
            'amount' => $this->formatMoney((float) $manualFinalTotal),
        ]];
    }

    private function sumLegAmounts(array $pricedLegs): string
    {
        $total = array_reduce($pricedLegs, function (float $carry, array $leg): float {
            return $carry + (float) $leg['amount'];
        }, 0.0);

        return $this->formatMoney($total);
    }

    private function sumSharedMiles(array $pricedLegs, string $key): int
    {
        return array_reduce($pricedLegs, function (int $carry, array $leg) use ($key): int {
            return $carry + $leg[$key];
        }, 0);
    }

    private function formatMoney(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }

    private function formatRate(float $rate, int $precision = 6): string
    {
        return number_format(round($rate, $precision), $precision, '.', '');
    }
}
