<?php

namespace Tests\Unit\Pricing;

use App\Services\Pricing\DeterministicPricingCalculator;
use PHPUnit\Framework\TestCase;

class DeterministicPricingCalculatorSharedLoadTest extends TestCase
{
    public function test_it_prices_shared_allocation_legs_with_full_and_split_sections(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $quote = $calculator->calculateSharedAllocation([
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
            'shared_load_percentage' => '0.750000',
            'allocation_legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'route_miles' => 10,
                    'rate_type' => 'unloaded',
                    'full_miles' => 6,
                    'split_miles' => 4,
                    'split_divisor' => 2,
                    'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'route_miles' => 90,
                    'rate_type' => 'loaded',
                    'full_miles' => 60,
                    'split_miles' => 30,
                    'split_divisor' => 2,
                    'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'route_miles' => 96,
                    'rate_type' => 'unloaded',
                    'full_miles' => 96,
                    'split_miles' => 0,
                    'split_divisor' => null,
                    'reason' => 'The return to depot is this customer’s own section after the shared route ends.',
                ],
            ],
        ]);

        $this->assertSame('240.87', $quote['engine_total']);
        $this->assertSame('240.87', $quote['final_total']);
        $this->assertSame('162', $quote['shared_load']['full_charge_miles']);
        $this->assertSame('34', $quote['shared_load']['split_charge_miles']);
        $this->assertSame('0.750000', $quote['shared_load']['shared_load_percentage']);
        $this->assertSame('7.37', $quote['shared_load']['allocation_legs'][0]['amount']);
        $this->assertSame('39.56', $quote['shared_load']['allocation_legs'][1]['split_amount']);
        $this->assertSame('0.750000', $quote['shared_load']['allocation_legs'][1]['shared_load_percentage']);
        $this->assertSame('The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.', $quote['shared_load']['allocation_legs'][1]['reason']);
    }

    public function test_it_rejects_shared_load_percentages_above_one_hundred_per_cent(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [shared_load_percentage] pricing input must not be greater than 1.');

        $calculator->calculateSharedAllocation([
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
            'shared_load_percentage' => '1.250000',
            'allocation_legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'route_miles' => 10,
                    'rate_type' => 'unloaded',
                    'full_miles' => 6,
                    'split_miles' => 4,
                    'split_divisor' => 2,
                    'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'route_miles' => 90,
                    'rate_type' => 'loaded',
                    'full_miles' => 60,
                    'split_miles' => 30,
                    'split_divisor' => 2,
                    'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'route_miles' => 96,
                    'rate_type' => 'unloaded',
                    'full_miles' => 96,
                    'split_miles' => 0,
                    'split_divisor' => null,
                    'reason' => 'The return to depot is this customer’s own section after the shared route ends.',
                ],
            ],
        ]);
    }

    public function test_it_rejects_fractional_shared_allocation_miles_in_direct_pricing_input(): void
    {
        $calculator = new DeterministicPricingCalculator;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The [allocation_legs.0.full_miles] pricing input must be a whole number.');

        $calculator->calculateSharedAllocation([
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
            'shared_load_percentage' => '0.750000',
            'allocation_legs' => [
                [
                    'label' => 'depot_to_pickup',
                    'route_miles' => 10,
                    'rate_type' => 'unloaded',
                    'full_miles' => '5.5',
                    'split_miles' => 4,
                    'split_divisor' => 2,
                    'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.',
                ],
                [
                    'label' => 'pickup_to_dropoff',
                    'route_miles' => 90,
                    'rate_type' => 'loaded',
                    'full_miles' => 60,
                    'split_miles' => 30,
                    'split_divisor' => 2,
                    'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.',
                ],
                [
                    'label' => 'dropoff_to_depot',
                    'route_miles' => 96,
                    'rate_type' => 'unloaded',
                    'full_miles' => 96,
                    'split_miles' => 0,
                    'split_divisor' => null,
                    'reason' => 'The return to depot is this customer’s own section after the shared route ends.',
                ],
            ],
        ]);
    }
}
