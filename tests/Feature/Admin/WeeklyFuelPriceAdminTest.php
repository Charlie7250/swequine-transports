<?php

namespace Tests\Feature\Admin;

use App\Models\FuelPriceSource;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyFuelPriceAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_add_a_weekly_fuel_price_and_make_it_active(): void
    {
        $this->actingAs(User::factory()->create());
        FuelPriceSource::factory()->texaco()->create();

        $currentActive = WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
            'activated_at' => now()->subWeek(),
        ]);

        $this->post('/admin/weekly-fuel-prices', [
            'week_commencing' => '2026-08-17',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5700',
            'activate_now' => '1',
        ])->assertRedirect('/admin/weekly-fuel-prices');

        $currentActive->refresh();
        $newActive = WeeklyFuelPrice::query()->latest('id')->firstOrFail();

        $this->assertFalse($currentActive->is_active);
        $this->assertTrue($newActive->is_active);
        $this->assertNotNull($newActive->activated_at);
        $this->assertSame('2026-08-17', $newActive->week_commencing?->format('Y-m-d'));
    }

    public function test_staff_can_activate_a_history_entry_as_the_manual_override(): void
    {
        $this->actingAs(User::factory()->create());

        $currentActive = WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-17',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5700',
            'is_active' => true,
            'activated_at' => now(),
        ]);
        $historyEntry = WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => false,
        ]);

        $this->post("/admin/weekly-fuel-prices/{$historyEntry->id}/activate")
            ->assertRedirect('/admin/weekly-fuel-prices');

        $currentActive->refresh();
        $historyEntry->refresh();

        $this->assertFalse($currentActive->is_active);
        $this->assertTrue($historyEntry->is_active);
        $this->assertNotNull($historyEntry->activated_at);
    }
}
