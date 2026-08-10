<?php

namespace Tests\Feature\Database;

use App\Models\RateSetting;
use App\Models\WeeklyFuelPrice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivePricingRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_weekly_fuel_price_can_be_active(): void
    {
        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
        ]);

        $this->expectException(QueryException::class);

        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-17',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5700',
            'is_active' => true,
        ]);
    }

    public function test_only_one_rate_setting_can_be_active(): void
    {
        RateSetting::query()->create([
            'name' => 'Primary rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
        ]);

        $this->expectException(QueryException::class);

        RateSetting::query()->create([
            'name' => 'Secondary rates',
            'depot_postcode' => 'EX16 1AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
        ]);
    }
}
