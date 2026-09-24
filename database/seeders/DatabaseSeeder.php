<?php

namespace Database\Seeders;

use App\Models\FuelPriceSource;
use App\Models\RateSetting;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        FuelPriceSource::query()->firstOrCreate([
            'key' => 'manual_texaco_entry',
        ], [
            'display_name' => 'Texaco (manual entry)',
        ]);

        WeeklyFuelPrice::query()->firstOrCreate([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
        ], [
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        RateSetting::query()->firstOrCreate([
            'name' => 'Initial internal rates',
        ], [
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'shared_load_percentage' => '0.750000',
            'two_horse_multiplier' => '1.750000',
            'loading_practice_within_15_miles_price' => '30.00',
            'loading_practice_within_25_miles_price' => '45.00',
            'loading_practice_within_50_miles_price' => '90.00',
            'loading_practice_on_site_hourly_rate' => '25.00',
            'loading_practice_livery_day_rate' => '35.00',
            'loading_practice_livery_week_rate' => '220.00',
            'loading_practice_livery_fortnight_rate' => '410.00',
            'is_active' => true,
            'effective_from' => '2026-08-10',
        ]);

        if (app()->environment(['local', 'testing'])) {
            User::query()->firstOrCreate([
                'email' => 'ops@sweq.local',
            ], [
                'name' => 'SWES Operations',
                'password' => 'password',
                'can_manage_quote_exceptions' => true,
            ]);
        }
    }
}
