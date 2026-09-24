<?php

namespace Tests\Unit\Pricing;

use App\Services\Pricing\DeterministicPricingCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DeterministicPricingCalculatorTest extends TestCase
{
    public function test_one_horse_quotes_apply_the_configured_loaded_rate_multiplier(): void
    {
        $quote = (new DeterministicPricingCalculator)->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                ['label' => 'depot_to_pickup', 'miles' => 10, 'rate_type' => 'unloaded'],
                ['label' => 'pickup_to_dropoff', 'miles' => 90, 'rate_type' => 'loaded'],
                ['label' => 'dropoff_to_depot', 'miles' => 96, 'rate_type' => 'unloaded'],
            ],
        ]);

        $this->assertSame('1.172188', $quote['resolved_rates']['base_loaded_rate_per_mile']);
        $this->assertSame('1.758282', $quote['resolved_rates']['loaded_rate_per_mile']);
        $this->assertSame('255.90', $quote['engine_total']);
        $this->assertSame(1, $quote['horse_count']['count']);
        $this->assertSame('1.500000', $quote['horse_count']['multiplier']);
    }

    public function test_two_horse_quotes_apply_the_configured_loaded_rate_multiplier(): void
    {
        $quote = (new DeterministicPricingCalculator)->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 2,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                ['label' => 'depot_to_pickup', 'miles' => 10, 'rate_type' => 'unloaded'],
                ['label' => 'pickup_to_dropoff', 'miles' => 90, 'rate_type' => 'loaded'],
                ['label' => 'dropoff_to_depot', 'miles' => 96, 'rate_type' => 'unloaded'],
            ],
        ]);

        $this->assertSame('2.051329', $quote['resolved_rates']['loaded_rate_per_mile']);
        $this->assertSame('282.27', $quote['engine_total']);
        $this->assertSame(2, $quote['horse_count']['count']);
        $this->assertSame('1.750000', $quote['horse_count']['multiplier']);
    }

    public function test_automatic_pricing_rejects_horse_counts_above_two(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Automatic transport pricing supports one or two horses. Counts above two require manual review.');

        (new DeterministicPricingCalculator)->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 3,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                ['label' => 'depot_to_pickup', 'miles' => 10, 'rate_type' => 'unloaded'],
                ['label' => 'pickup_to_dropoff', 'miles' => 90, 'rate_type' => 'loaded'],
                ['label' => 'dropoff_to_depot', 'miles' => 96, 'rate_type' => 'unloaded'],
            ],
        ]);
    }

    public function test_line_items_are_rounded_before_the_engine_total_is_summed(): void
    {
        $quote = (new DeterministicPricingCalculator)->calculate($this->pricingInput([
            ['label' => 'depot_to_pickup', 'miles' => 5, 'rate_type' => 'unloaded'],
            ['label' => 'pickup_to_dropoff', 'miles' => 10, 'rate_type' => 'loaded'],
            ['label' => 'dropoff_to_depot', 'miles' => 12, 'rate_type' => 'unloaded'],
        ]));

        $this->assertSame('4.61', $quote['legs'][0]['amount']);
        $this->assertSame('17.58', $quote['legs'][1]['amount']);
        $this->assertSame('11.06', $quote['legs'][2]['amount']);
        $this->assertSame('33.25', $quote['engine_total']);
    }

    public function test_short_journey_boundary_does_not_apply_an_automatic_adjustment(): void
    {
        $underBoundary = (new DeterministicPricingCalculator)->calculate($this->pricingInput([
            ['label' => 'depot_to_pickup', 'miles' => 10, 'rate_type' => 'unloaded'],
            ['label' => 'pickup_to_dropoff', 'miles' => 50, 'rate_type' => 'loaded'],
            ['label' => 'dropoff_to_depot', 'miles' => 39, 'rate_type' => 'unloaded'],
        ]));
        $atBoundary = (new DeterministicPricingCalculator)->calculate($this->pricingInput([
            ['label' => 'depot_to_pickup', 'miles' => 10, 'rate_type' => 'unloaded'],
            ['label' => 'pickup_to_dropoff', 'miles' => 50, 'rate_type' => 'loaded'],
            ['label' => 'dropoff_to_depot', 'miles' => 40, 'rate_type' => 'unloaded'],
        ]));

        $this->assertSame('133.05', $underBoundary['engine_total']);
        $this->assertSame('133.05', $underBoundary['final_total']);
        $this->assertSame('133.97', $atBoundary['engine_total']);
        $this->assertSame('133.97', $atBoundary['final_total']);
    }

    public function test_unstructured_extra_uses_a_final_total_override(): void
    {
        $quote = (new DeterministicPricingCalculator)->calculate($this->pricingInput([
            ['label' => 'depot_to_pickup', 'miles' => 10, 'rate_type' => 'unloaded'],
            ['label' => 'pickup_to_dropoff', 'miles' => 90, 'rate_type' => 'loaded'],
            ['label' => 'dropoff_to_depot', 'miles' => 96, 'rate_type' => 'unloaded'],
        ], ['manual_final_total' => '280.90']));

        $this->assertSame('255.90', $quote['engine_total']);
        $this->assertSame('280.90', $quote['final_total']);
        $this->assertSame('manual_final_total', $quote['overrides'][0]['type']);
    }

    public function test_it_derives_rates_and_prices_three_explicit_route_legs(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $quote = $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 10,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'loaded',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 96,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);

        $this->assertSame('0.365736', $quote['resolved_rates']['base_cost_per_mile']);
        $this->assertSame('0.921292', $quote['resolved_rates']['unloaded_rate_per_mile']);
        $this->assertSame('1.172188', $quote['resolved_rates']['base_loaded_rate_per_mile']);
        $this->assertSame('1.758282', $quote['resolved_rates']['loaded_rate_per_mile']);
        $this->assertSame('255.90', $quote['engine_total']);
        $this->assertSame('255.90', $quote['final_total']);
        $this->assertSame('158.25', $quote['legs'][1]['amount']);
    }

    public function test_it_keeps_the_engine_total_when_no_manual_override_is_supplied(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $quote = $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 8.6,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 40.4,
                    'rate_type' => 'loaded',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 12.2,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);

        $this->assertSame(9, $quote['legs'][0]['miles']);
        $this->assertSame(40, $quote['legs'][1]['miles']);
        $this->assertSame(12, $quote['legs'][2]['miles']);
        $this->assertSame($quote['engine_total'], $quote['final_total']);
        $this->assertSame([], $quote['overrides']);
    }

    public function test_it_ignores_a_blank_manual_final_total_override(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $quote = $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'manual_final_total' => '',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 10,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'loaded',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 96,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);

        $this->assertSame('255.90', $quote['engine_total']);
        $this->assertSame($quote['engine_total'], $quote['final_total']);
        $this->assertSame([], $quote['overrides']);
    }

    public function test_it_rejects_unknown_leg_rate_types(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported leg rate type [shared].');

        $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 10,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'shared',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 96,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);
    }

    public function test_it_rejects_quotes_without_the_standard_three_route_legs(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Standard single quotes require exactly three route legs: depot_to_pickup, pickup_to_dropoff, and dropoff_to_depot.');

        $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 10,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'loaded',
                ],
            ],
        ]);
    }

    public function test_it_rejects_blank_fuel_prices_instead_of_treating_them_as_zero(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [fuel_price_per_litre_inc_vat] pricing input is required.');

        $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 10,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'loaded',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 96,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);
    }

    public function test_it_rejects_route_legs_with_zero_miles(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [legs.0.miles] pricing input must be greater than zero.');

        $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 0,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'loaded',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 96,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);
    }

    public function test_it_rejects_negative_maintenance_costs(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [maintenance_per_mile] pricing input must be zero or greater.');

        $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '-0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 10,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'loaded',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 96,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);
    }

    public function test_it_rejects_negative_loaded_add_ons(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [loaded_add_on_per_mile] pricing input must be zero or greater.');

        $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '-0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 10,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'loaded',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 96,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);
    }

    public function test_it_rejects_negative_manual_final_totals(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [manual_final_total] pricing input must be greater than zero.');

        $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'manual_final_total' => '-1.00',
            'legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'miles' => 10,
                    'rate_type' => 'unloaded',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'miles' => 90,
                    'rate_type' => 'loaded',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'miles' => 96,
                    'rate_type' => 'unloaded',
                ],
            ],
        ]);
    }

    private function pricingInput(array $legs, array $overrides = []): array
    {
        return array_merge([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
            'horse_count' => 1,
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.750000',
            'legs' => $legs,
        ], $overrides);
    }
}
