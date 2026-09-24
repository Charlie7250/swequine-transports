<?php

namespace Tests\Feature\Admin;

use App\Models\RateSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateSettingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_add_a_rate_setting_and_make_it_active(): void
    {
        $this->actingAs(User::factory()->create());

        $currentActive = RateSetting::query()->create([
            'name' => 'Current rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'shared_load_percentage' => '0.750000',
            'two_horse_multiplier' => '1.150000',
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

        $this->post('/admin/rate-settings', [
            'name' => 'Autumn rates',
            'depot_postcode' => 'EX16 5PQ',
            'miles_per_gallon' => '22.5000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.060000',
            'unloaded_add_on_per_mile' => '0.605556',
            'loaded_add_on_per_mile' => '0.856452',
            'one_horse_multiplier' => '1.500000',
            'shared_load_percentage' => '0.820000',
            'two_horse_multiplier' => '1.750000',
            'loading_practice_within_15_miles_price' => '32.00',
            'loading_practice_within_25_miles_price' => '48.00',
            'loading_practice_within_50_miles_price' => '95.00',
            'loading_practice_on_site_hourly_rate' => '27.50',
            'loading_practice_livery_day_rate' => '38.00',
            'loading_practice_livery_week_rate' => '230.00',
            'loading_practice_livery_fortnight_rate' => '420.00',
            'effective_from' => '2026-08-17',
            'activate_now' => '1',
        ])->assertRedirect('/admin/rate-settings');

        $currentActive->refresh();
        $newActive = RateSetting::query()->where('name', 'Autumn rates')->firstOrFail();

        $this->assertFalse($currentActive->is_active);
        $this->assertTrue($newActive->is_active);
        $this->assertSame('EX16 5PQ', $newActive->depot_postcode);
        $this->assertSame('1.500000', $newActive->one_horse_multiplier);
        $this->assertSame('1.750000', $newActive->two_horse_multiplier);
        $this->assertSame('0.820000', $newActive->shared_load_percentage);
        $this->assertSame('95.00', $newActive->loading_practice_within_50_miles_price);
        $this->assertSame('27.50', $newActive->loading_practice_on_site_hourly_rate);
        $this->assertSame('420.00', $newActive->loading_practice_livery_fortnight_rate);
    }

    public function test_rate_settings_page_groups_transport_inputs_separately_from_loading_practice_prices(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/rate-settings')
            ->assertOk()
            ->assertSeeText('Transport rate inputs')
            ->assertSeeText('Loading practice prices');
    }

    public function test_staff_cannot_store_a_shared_load_percentage_above_one_hundred_per_cent(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from('/admin/rate-settings')
            ->post('/admin/rate-settings', [
                'name' => 'Autumn rates',
                'depot_postcode' => 'EX16 5PQ',
                'miles_per_gallon' => '22.5000',
                'litres_per_gallon' => '4.5400',
                'maintenance_per_mile' => '0.060000',
                'unloaded_add_on_per_mile' => '0.605556',
                'loaded_add_on_per_mile' => '0.856452',
                'one_horse_multiplier' => '1.500000',
                'shared_load_percentage' => '1.250000',
                'two_horse_multiplier' => '1.180000',
                'loading_practice_within_15_miles_price' => '32.00',
                'loading_practice_within_25_miles_price' => '48.00',
                'loading_practice_within_50_miles_price' => '95.00',
                'loading_practice_on_site_hourly_rate' => '27.50',
                'loading_practice_livery_day_rate' => '38.00',
                'loading_practice_livery_week_rate' => '230.00',
                'loading_practice_livery_fortnight_rate' => '420.00',
                'effective_from' => '2026-08-17',
                'activate_now' => '1',
            ])
            ->assertRedirect('/admin/rate-settings')
            ->assertSessionHasErrors('shared_load_percentage');

        $this->assertDatabaseCount('rate_settings', 0);
    }
}
