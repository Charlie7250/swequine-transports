<?php

namespace Tests\Feature\Admin;

use App\Models\FuelPriceSource;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelPriceSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_for_returns_the_display_name_of_a_known_source(): void
    {
        FuelPriceSource::factory()->texaco()->create();

        $this->assertSame('Texaco (manual entry)', FuelPriceSource::labelFor('manual_texaco_entry'));
    }

    public function test_label_for_falls_back_to_a_readable_form_of_an_unknown_key(): void
    {
        $this->assertSame('Manual Legacy Source', FuelPriceSource::labelFor('manual_legacy_source'));
    }

    public function test_weekly_fuel_page_offers_known_sources_as_dropdown_options(): void
    {
        $this->actingAs(User::factory()->create());
        FuelPriceSource::factory()->texaco()->create();

        $this->get('/admin/weekly-fuel-prices')
            ->assertOk()
            ->assertSeeText('Texaco (manual entry)');
    }

    public function test_weekly_fuel_entry_is_rejected_when_the_source_is_not_a_known_key(): void
    {
        $this->actingAs(User::factory()->create());
        FuelPriceSource::factory()->texaco()->create();

        $this->from('/admin/weekly-fuel-prices')
            ->post('/admin/weekly-fuel-prices', [
                'week_commencing' => '2026-08-24',
                'source' => 'not_a_real_source',
                'price_per_litre_inc_vat' => '1.6000',
                'activate_now' => '1',
            ])
            ->assertRedirect('/admin/weekly-fuel-prices')
            ->assertSessionHasErrors('source');

        $this->assertDatabaseCount('weekly_fuel_prices', 0);
    }

    public function test_staff_can_add_a_fuel_source_and_then_select_it(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/admin/weekly-fuel-prices/sources', [
            'display_name' => 'Asda (manual entry)',
        ])->assertRedirect('/admin/weekly-fuel-prices');

        $this->assertDatabaseHas('fuel_price_sources', [
            'key' => 'asda_manual_entry',
            'display_name' => 'Asda (manual entry)',
        ]);

        $this->get('/admin/weekly-fuel-prices')
            ->assertOk()
            ->assertSeeText('Asda (manual entry)');
    }

    public function test_adding_a_duplicate_fuel_source_display_name_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        FuelPriceSource::factory()->texaco()->create();

        $this->from('/admin/weekly-fuel-prices')
            ->post('/admin/weekly-fuel-prices/sources', [
                'display_name' => 'Texaco (manual entry)',
            ])
            ->assertRedirect('/admin/weekly-fuel-prices')
            ->assertSessionHasErrors('display_name');

        $this->assertDatabaseCount('fuel_price_sources', 1);
    }

    public function test_weekly_fuel_history_shows_the_source_display_name_not_the_raw_key(): void
    {
        $this->actingAs(User::factory()->create());
        FuelPriceSource::factory()->texaco()->create();
        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-17',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5700',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $this->get('/admin/weekly-fuel-prices')
            ->assertOk()
            ->assertSeeText('Texaco (manual entry)')
            ->assertDontSeeText('manual_texaco_entry');
    }
}
