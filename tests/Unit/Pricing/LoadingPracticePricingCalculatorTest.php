<?php

namespace Tests\Unit\Pricing;

use App\Services\Pricing\LoadingPracticePricingCalculator;
use Tests\TestCase;

class LoadingPracticePricingCalculatorTest extends TestCase
{
    public function test_it_calculates_a_within_twenty_five_mile_loading_practice_quote(): void
    {
        $quote = app(LoadingPracticePricingCalculator::class)->calculate([
            'travel_miles' => 24,
            'on_site_hours' => '1.50',
            'handling_livery_period' => 'day',
            'handling_livery_quantity' => 2,
            'loading_practice_within_15_miles_price' => '30.00',
            'loading_practice_within_25_miles_price' => '45.00',
            'loading_practice_within_50_miles_price' => '90.00',
            'loading_practice_on_site_hourly_rate' => '25.00',
            'loading_practice_livery_day_rate' => '35.00',
            'loading_practice_livery_week_rate' => '220.00',
            'loading_practice_livery_fortnight_rate' => '410.00',
        ]);

        $this->assertSame('within_25_miles', $quote['distance_band']);
        $this->assertFalse($quote['is_poa']);
        $this->assertSame('45.00', $quote['package_price']);
        $this->assertSame('25.00', $quote['on_site']['hourly_rate']);
        $this->assertSame('37.50', $quote['on_site']['amount']);
        $this->assertSame('day', $quote['handling_livery']['period']);
        $this->assertSame(2, $quote['handling_livery']['quantity']);
        $this->assertSame('35.00', $quote['handling_livery']['rate']);
        $this->assertSame('70.00', $quote['handling_livery']['amount']);
        $this->assertSame('152.50', $quote['engine_total']);
        $this->assertSame('152.50', $quote['final_total']);
    }

    public function test_it_marks_quotes_over_fifty_miles_as_poa_and_uses_a_manual_final_total_when_present(): void
    {
        $quote = app(LoadingPracticePricingCalculator::class)->calculate([
            'travel_miles' => 51,
            'on_site_hours' => '2.00',
            'manual_final_total' => '240.00',
            'loading_practice_within_15_miles_price' => '30.00',
            'loading_practice_within_25_miles_price' => '45.00',
            'loading_practice_within_50_miles_price' => '90.00',
            'loading_practice_on_site_hourly_rate' => '25.00',
            'loading_practice_livery_day_rate' => '35.00',
            'loading_practice_livery_week_rate' => '220.00',
            'loading_practice_livery_fortnight_rate' => '410.00',
        ]);

        $this->assertSame('over_50_miles', $quote['distance_band']);
        $this->assertTrue($quote['is_poa']);
        $this->assertNull($quote['package_price']);
        $this->assertNull($quote['engine_total']);
        $this->assertSame('240.00', $quote['final_total']);
        $this->assertSame('manual_final_total', $quote['overrides'][0]['type']);
        $this->assertSame('240.00', $quote['overrides'][0]['amount']);
    }
}
