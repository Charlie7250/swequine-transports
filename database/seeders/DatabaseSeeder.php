<?php

namespace Database\Seeders;

use App\Models\RateSetting;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
            'effective_from' => '2026-08-10',
        ]);

        if (app()->environment(['local', 'testing'])) {
            User::query()->firstOrCreate([
                'email' => 'ops@sweq.local',
            ], [
                'name' => 'SWES Operations',
                'password' => 'password',
            ]);
        }
    }
}
