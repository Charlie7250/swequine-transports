<?php

namespace App\Services\Pricing;

use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteLeg;
use App\Models\SharedRunAllocation;
use App\Models\WeeklyFuelPrice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class JobRevisionPricingEngine
{
    public function __construct(
        private readonly DeterministicPricingCalculator $calculator,
        private readonly JobRevisionPricingContextResolver $contextResolver,
    ) {}

    public function price(JobRevision $revision, ?string $manualFinalTotal = null): array
    {
        $pricingContext = $this->contextResolver->resolve($revision);
        $weeklyFuelPrice = $pricingContext['weekly_fuel_price'];
        $rateSetting = $pricingContext['rate_setting'];

        if ($weeklyFuelPrice === null) {
            throw new InvalidArgumentException('Job revision pricing requires an assigned weekly fuel price or one active weekly fuel price.');
        }

        if ($rateSetting === null) {
            throw new InvalidArgumentException('Job revision pricing requires an assigned rate setting or one active rate setting.');
        }

        $routeLegs = $revision->routeLegs()->orderBy('sequence')->get();
        $sharedRunAllocation = $revision->sharedRunAllocation()->with('sharedRun')->first();
        $pricingInput = $this->buildPricingInput($revision, $weeklyFuelPrice, $rateSetting, $manualFinalTotal);
        $calculation = $sharedRunAllocation === null
            ? $this->calculator->calculate(array_merge($pricingInput, [
                'legs' => $this->buildLegPricingInput($routeLegs),
            ]))
            : $this->calculator->calculateSharedAllocation(array_merge($pricingInput, [
                'allocation_legs' => $this->buildSharedAllocationLegPricingInput($routeLegs, $sharedRunAllocation),
            ]));

        DB::transaction(function () use ($revision, $weeklyFuelPrice, $rateSetting, $routeLegs, $calculation, $sharedRunAllocation, $manualFinalTotal): void {
            foreach ($routeLegs as $index => $routeLeg) {
                $routeLeg->forceFill([
                    'rate_per_mile' => $calculation['legs'][$index]['rate_per_mile'],
                    'amount' => $calculation['legs'][$index]['amount'],
                ])->save();
            }

            if ($sharedRunAllocation !== null) {
                $sharedRunAllocation->forceFill([
                    'full_charge_miles' => $calculation['shared_load']['full_charge_miles'],
                    'split_charge_miles' => $calculation['shared_load']['split_charge_miles'],
                    'total_charge' => $calculation['engine_total'],
                    'allocation_explanation' => $calculation['shared_load'],
                ])->save();
            }

            $revision->forceFill([
                'weekly_fuel_price_id' => $weeklyFuelPrice->id,
                'rate_setting_id' => $rateSetting->id,
                'engine_total' => $calculation['engine_total'],
                'final_total' => $calculation['final_total'],
                'manual_final_total_reason' => $manualFinalTotal === null ? null : $revision->manual_final_total_reason,
                'calculation_explanation' => $calculation,
            ])->save();
        });

        return $calculation;
    }

    private function buildLegPricingInput(Collection $routeLegs): array
    {
        return $routeLegs
            ->map(fn (RouteLeg $routeLeg): array => [
                'label' => $routeLeg->label,
                'miles' => $routeLeg->manual_miles ?? $routeLeg->miles,
                'rate_type' => $routeLeg->rate_type,
            ])
            ->all();
    }

    private function buildPricingInput(
        JobRevision $revision,
        WeeklyFuelPrice $weeklyFuelPrice,
        RateSetting $rateSetting,
        ?string $manualFinalTotal,
    ): array {
        return [
            'week_commencing' => $weeklyFuelPrice->week_commencing?->format('Y-m-d'),
            'fuel_source' => $weeklyFuelPrice->source,
            'fuel_price_per_litre_inc_vat' => $weeklyFuelPrice->price_per_litre_inc_vat,
            'miles_per_gallon' => $rateSetting->miles_per_gallon,
            'litres_per_gallon' => $rateSetting->litres_per_gallon,
            'maintenance_per_mile' => $rateSetting->maintenance_per_mile,
            'unloaded_add_on_per_mile' => $rateSetting->unloaded_add_on_per_mile,
            'loaded_add_on_per_mile' => $rateSetting->loaded_add_on_per_mile,
            'horse_count' => $revision->horse_count,
            'one_horse_multiplier' => $rateSetting->one_horse_multiplier,
            'two_horse_multiplier' => $rateSetting->two_horse_multiplier,
            'shared_load_percentage' => $rateSetting->shared_load_percentage,
            'manual_final_total' => $manualFinalTotal,
        ];
    }

    private function buildSharedAllocationLegPricingInput(
        Collection $routeLegs,
        SharedRunAllocation $sharedRunAllocation,
    ): array {
        $allocationLegs = collect($sharedRunAllocation->allocation_explanation['allocation_legs'] ?? [])
            ->keyBy('label');

        return $routeLegs
            ->map(function (RouteLeg $routeLeg) use ($allocationLegs): array {
                $allocationLeg = $allocationLegs->get($routeLeg->label);

                if (! is_array($allocationLeg)) {
                    throw new InvalidArgumentException("Shared allocation pricing requires a stored allocation leg for [{$routeLeg->label}].");
                }

                return [
                    'label' => $routeLeg->label,
                    'route_miles' => $routeLeg->manual_miles ?? $routeLeg->miles,
                    'rate_type' => $routeLeg->rate_type,
                    'full_miles' => $allocationLeg['full_miles'] ?? null,
                    'split_miles' => $allocationLeg['split_miles'] ?? null,
                    'split_divisor' => $allocationLeg['split_divisor'] ?? null,
                    'reason' => $allocationLeg['reason'] ?? null,
                ];
            })
            ->all();
    }
}
