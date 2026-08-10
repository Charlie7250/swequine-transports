<?php

namespace App\Services\Pricing;

class DeterministicPricingCalculator
{
    public function calculate(array $input): array
    {
        $resolvedRates = $this->resolveRates($input);
        $pricedLegs = $this->priceLegs($input['legs'], $resolvedRates);
        $engineTotal = $this->sumLegAmounts($pricedLegs);
        $manualFinalTotal = $input['manual_final_total'] ?? null;
        $overrides = $this->buildOverrides($manualFinalTotal);
        $finalTotal = $manualFinalTotal === null ? $engineTotal : $this->formatMoney((float) $manualFinalTotal);

        return [
            'fuel_context' => [
                'week_commencing' => $input['week_commencing'],
                'source' => $input['fuel_source'],
                'price_per_litre_inc_vat' => $this->formatRate((float) $input['fuel_price_per_litre_inc_vat'], 4),
            ],
            'rate_inputs' => [
                'miles_per_gallon' => $this->formatRate((float) $input['miles_per_gallon'], 4),
                'litres_per_gallon' => $this->formatRate((float) $input['litres_per_gallon'], 4),
                'maintenance_per_mile' => $this->formatRate((float) $input['maintenance_per_mile']),
                'unloaded_add_on_per_mile' => $this->formatRate((float) $input['unloaded_add_on_per_mile']),
                'loaded_add_on_per_mile' => $this->formatRate((float) $input['loaded_add_on_per_mile']),
            ],
            'resolved_rates' => $resolvedRates,
            'legs' => $pricedLegs,
            'extras' => [],
            'overrides' => $overrides,
            'engine_total' => $engineTotal,
            'final_total' => $finalTotal,
        ];
    }

    private function resolveRates(array $input): array
    {
        $fuelPrice = (float) $input['fuel_price_per_litre_inc_vat'];
        $litresPerGallon = (float) $input['litres_per_gallon'];
        $milesPerGallon = (float) $input['miles_per_gallon'];
        $maintenancePerMile = (float) $input['maintenance_per_mile'];
        $unloadedAddOn = (float) $input['unloaded_add_on_per_mile'];
        $loadedAddOn = (float) $input['loaded_add_on_per_mile'];

        $baseCostPerMile = round((($fuelPrice * $litresPerGallon) / $milesPerGallon) + $maintenancePerMile, 6);

        return [
            'base_cost_per_mile' => $this->formatRate($baseCostPerMile),
            'unloaded_rate_per_mile' => $this->formatRate(round($baseCostPerMile + $unloadedAddOn, 6)),
            'loaded_rate_per_mile' => $this->formatRate(round($baseCostPerMile + $loadedAddOn, 6)),
        ];
    }

    private function priceLegs(array $legs, array $resolvedRates): array
    {
        return array_map(function (array $leg) use ($resolvedRates): array {
            $miles = (int) round((float) $leg['miles']);
            $rateKey = $leg['rate_type'] === 'loaded' ? 'loaded_rate_per_mile' : 'unloaded_rate_per_mile';
            $ratePerMile = (float) $resolvedRates[$rateKey];
            $amount = round($miles * $ratePerMile, 2);

            return [
                'label' => $leg['label'],
                'miles' => $miles,
                'rate_type' => $leg['rate_type'],
                'rate_per_mile' => $this->formatRate($ratePerMile),
                'amount' => $this->formatMoney($amount),
            ];
        }, $legs);
    }

    private function sumLegAmounts(array $pricedLegs): string
    {
        $total = array_reduce($pricedLegs, function (float $carry, array $leg): float {
            return $carry + (float) $leg['amount'];
        }, 0.0);

        return $this->formatMoney($total);
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

    private function formatMoney(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }

    private function formatRate(float $rate, int $precision = 6): string
    {
        return number_format(round($rate, $precision), $precision, '.', '');
    }
}
