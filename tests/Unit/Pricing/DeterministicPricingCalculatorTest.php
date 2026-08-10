<?php

namespace Tests\Unit\Pricing;

use App\Services\Pricing\DeterministicPricingCalculator;
use PHPUnit\Framework\TestCase;

class DeterministicPricingCalculatorTest extends TestCase
{
    public function test_it_derives_rates_and_prices_three_explicit_route_legs(): void
    {
        $calculator = new DeterministicPricingCalculator();

        $quote = $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
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
        $this->assertSame('1.172188', $quote['resolved_rates']['loaded_rate_per_mile']);
        $this->assertSame('203.15', $quote['engine_total']);
        $this->assertSame('203.15', $quote['final_total']);
        $this->assertSame('105.50', $quote['legs'][1]['amount']);
    }

    public function test_it_keeps_the_engine_total_when_no_manual_override_is_supplied(): void
    {
        $calculator = new DeterministicPricingCalculator();

        $quote = $calculator->calculate([
            'week_commencing' => '2026-08-10',
            'fuel_source' => 'manual_texaco_entry',
            'fuel_price_per_litre_inc_vat' => '1.53',
            'miles_per_gallon' => '22',
            'litres_per_gallon' => '4.54',
            'maintenance_per_mile' => '0.05',
            'unloaded_add_on_per_mile' => '0.5555555556',
            'loaded_add_on_per_mile' => '0.8064516129',
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
}
