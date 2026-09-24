<?php

namespace Tests\Feature\LoadingPractice;

use App\Models\LoadingPracticeQuote;
use App\Models\RateSetting;
use App\Models\User;
use App\Services\Pricing\RateSettingManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoadingPracticeQuoteFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_a_loading_practice_quote_with_configured_package_pricing(): void
    {
        $this->actingAs(User::factory()->create());

        $rateSetting = $this->createRateSetting([
            'is_active' => true,
            'loading_practice_within_15_miles_price' => '30.00',
            'loading_practice_within_25_miles_price' => '45.00',
            'loading_practice_within_50_miles_price' => '90.00',
            'loading_practice_on_site_hourly_rate' => '25.00',
            'loading_practice_livery_day_rate' => '35.00',
            'loading_practice_livery_week_rate' => '220.00',
            'loading_practice_livery_fortnight_rate' => '410.00',
        ]);

        $response = $this->post('/loading-practice-quotes', [
            'customer_name' => 'Amber Vale Eventing',
            'customer_contact_name' => 'Harriet Vale',
            'customer_email' => 'harriet@example.test',
            'customer_phone' => '07700 900123',
            'customer_postcode' => 'EX2 4AB',
            'customer_notes' => 'Sensitive young horse',
            'travel_miles' => 24,
            'on_site_hours' => '1.50',
            'handling_livery_period' => 'day',
            'handling_livery_quantity' => 2,
            'manual_final_total' => '',
            'manual_final_total_reason' => '',
            'quote_notes' => 'Use the south arena if free',
        ]);

        $quote = LoadingPracticeQuote::query()->with('customer')->firstOrFail();

        $response->assertRedirect("/loading-practice-quotes/{$quote->id}");
        $this->assertSame($rateSetting->id, $quote->rate_setting_id);
        $this->assertSame('Amber Vale Eventing', $quote->customer->name);
        $this->assertSame('within_25_miles', $quote->distance_band);
        $this->assertSame(24, $quote->travel_miles);
        $this->assertSame('45.00', $quote->package_price);
        $this->assertSame('1.50', $quote->on_site_hours);
        $this->assertSame('25.00', $quote->on_site_hourly_rate);
        $this->assertSame('day', $quote->handling_livery_period);
        $this->assertSame(2, $quote->handling_livery_quantity);
        $this->assertSame('35.00', $quote->handling_livery_rate);
        $this->assertFalse($quote->is_poa);
        $this->assertSame('152.50', $quote->engine_total);
        $this->assertSame('152.50', $quote->final_total);
        $this->assertSame('45.00', $quote->calculation_explanation['package_price']);
        $this->assertSame('37.50', $quote->calculation_explanation['on_site']['amount']);
        $this->assertSame('70.00', $quote->calculation_explanation['handling_livery']['amount']);
    }

    public function test_loading_practice_quotes_over_fifty_miles_are_saved_as_poa_with_a_manual_amount_when_entered(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createRateSetting([
            'is_active' => true,
        ]);

        $response = $this->post('/loading-practice-quotes', [
            'customer_name' => 'Amber Vale Eventing',
            'travel_miles' => 51,
            'on_site_hours' => '2.00',
            'handling_livery_period' => '',
            'handling_livery_quantity' => '',
            'manual_final_total' => '240.00',
            'manual_final_total_reason' => 'Long-distance loading day agreed',
            'quote_notes' => 'Manual price required',
        ]);

        $quote = LoadingPracticeQuote::query()->firstOrFail();

        $response->assertRedirect("/loading-practice-quotes/{$quote->id}");
        $this->assertSame('over_50_miles', $quote->distance_band);
        $this->assertTrue($quote->is_poa);
        $this->assertNull($quote->package_price);
        $this->assertNull($quote->engine_total);
        $this->assertSame('240.00', $quote->final_total);
        $this->assertSame('Long-distance loading day agreed', $quote->manual_final_total_reason);
        $this->assertSame('manual_final_total', $quote->calculation_explanation['overrides'][0]['type']);
    }

    public function test_loading_practice_quotes_over_fifty_miles_require_a_manual_amount(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createRateSetting([
            'is_active' => true,
        ]);

        $this->from('/loading-practice-quotes/create')
            ->post('/loading-practice-quotes', [
                'customer_name' => 'Amber Vale Eventing',
                'travel_miles' => 51,
                'on_site_hours' => '2.00',
                'handling_livery_period' => '',
                'handling_livery_quantity' => '',
                'manual_final_total' => '',
                'manual_final_total_reason' => '',
                'quote_notes' => 'Manual price required',
            ])
            ->assertRedirect('/loading-practice-quotes/create')
            ->assertSessionHasErrors('manual_final_total');

        $this->assertDatabaseCount('loading_practice_quotes', 0);
    }

    public function test_loading_practice_quote_updates_keep_using_the_stored_rate_setting(): void
    {
        $this->actingAs(User::factory()->create());

        $storedRateSetting = $this->createRateSetting([
            'is_active' => true,
            'loading_practice_within_50_miles_price' => '90.00',
            'loading_practice_on_site_hourly_rate' => '25.00',
            'loading_practice_livery_week_rate' => '220.00',
        ]);

        $this->post('/loading-practice-quotes', [
            'customer_name' => 'Amber Vale Eventing',
            'travel_miles' => 40,
            'on_site_hours' => '2.00',
            'handling_livery_period' => 'week',
            'handling_livery_quantity' => 1,
            'manual_final_total' => '',
            'manual_final_total_reason' => '',
            'quote_notes' => '',
        ]);

        $quote = LoadingPracticeQuote::query()->firstOrFail();

        $replacementRateSetting = $this->createRateSetting([
            'is_active' => false,
            'loading_practice_within_50_miles_price' => '120.00',
            'loading_practice_on_site_hourly_rate' => '30.00',
            'loading_practice_livery_week_rate' => '260.00',
        ]);
        app(RateSettingManager::class)->activate($replacementRateSetting);

        $response = $this->patch("/loading-practice-quotes/{$quote->id}", [
            'customer_name' => 'Amber Vale Eventing',
            'travel_miles' => 40,
            'on_site_hours' => '1.00',
            'handling_livery_period' => 'week',
            'handling_livery_quantity' => 1,
            'manual_final_total' => '',
            'manual_final_total_reason' => '',
            'quote_notes' => 'Repriced with fewer on-site hours',
        ]);

        $quote->refresh();

        $response->assertRedirect("/loading-practice-quotes/{$quote->id}");
        $this->assertSame($storedRateSetting->id, $quote->rate_setting_id);
        $this->assertNotSame($replacementRateSetting->id, $quote->rate_setting_id);
        $this->assertSame('335.00', $quote->engine_total);
        $this->assertSame('335.00', $quote->final_total);
        $this->assertSame('25.00', $quote->on_site_hourly_rate);
        $this->assertSame('220.00', $quote->handling_livery_rate);
    }

    public function test_loading_practice_quote_update_rejects_repricing_without_a_stored_rate_setting(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createRateSetting([
            'is_active' => true,
        ]);

        $this->post('/loading-practice-quotes', [
            'customer_name' => 'Amber Vale Eventing',
            'travel_miles' => 24,
            'on_site_hours' => '1.00',
            'handling_livery_period' => '',
            'handling_livery_quantity' => '',
            'manual_final_total' => '',
            'manual_final_total_reason' => '',
            'quote_notes' => '',
        ]);

        $quote = LoadingPracticeQuote::query()->firstOrFail();
        $quote->forceFill([
            'rate_setting_id' => null,
        ])->save();

        $replacementRateSetting = $this->createRateSetting([
            'is_active' => false,
            'loading_practice_within_25_miles_price' => '60.00',
        ]);
        app(RateSettingManager::class)->activate($replacementRateSetting);

        $this->from("/loading-practice-quotes/{$quote->id}")
            ->patch("/loading-practice-quotes/{$quote->id}", [
                'customer_name' => 'Amber Vale Eventing',
                'travel_miles' => 24,
                'on_site_hours' => '1.00',
                'handling_livery_period' => '',
                'handling_livery_quantity' => '',
                'manual_final_total' => '',
                'manual_final_total_reason' => '',
                'quote_notes' => 'Attempted repricing',
            ])
            ->assertRedirect("/loading-practice-quotes/{$quote->id}")
            ->assertSessionHasErrors('pricing');

        $quote->refresh();

        $this->assertNull($quote->rate_setting_id);
        $this->assertSame('45.00', $quote->package_price);
        $this->assertSame('', $quote->notes ?? '');
    }

    public function test_loading_practice_show_makes_a_reasonless_manual_override_visible(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createRateSetting([
            'is_active' => true,
        ]);

        $this->post('/loading-practice-quotes', [
            'customer_name' => 'Amber Vale Eventing',
            'travel_miles' => 24,
            'on_site_hours' => '1.00',
            'handling_livery_period' => '',
            'handling_livery_quantity' => '',
            'manual_final_total' => '140.00',
            'manual_final_total_reason' => '',
            'quote_notes' => '',
        ]);

        $quote = LoadingPracticeQuote::query()->firstOrFail();

        $this->get("/loading-practice-quotes/{$quote->id}")
            ->assertOk()
            ->assertSeeText('Manual final total override recorded.');
    }

    public function test_loading_practice_show_keeps_an_equal_value_manual_override_visible(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createRateSetting([
            'is_active' => true,
        ]);

        $this->post('/loading-practice-quotes', [
            'customer_name' => 'Amber Vale Eventing',
            'travel_miles' => 24,
            'on_site_hours' => '1.00',
            'handling_livery_period' => '',
            'handling_livery_quantity' => '',
            'manual_final_total' => '70.00',
            'manual_final_total_reason' => '',
            'quote_notes' => '',
        ]);

        $quote = LoadingPracticeQuote::query()->firstOrFail();

        $this->assertSame('manual_final_total', $quote->calculation_explanation['overrides'][0]['type']);

        $this->get("/loading-practice-quotes/{$quote->id}")
            ->assertOk()
            ->assertSeeText('Manual final total override recorded.')
            ->assertSee('name="manual_final_total"', false)
            ->assertSee('value="70.00"', false);
    }

    public function test_loading_practice_show_reports_a_missing_stored_rate_setting_instead_of_falling_back_to_the_active_default(): void
    {
        $this->actingAs(User::factory()->create());

        $this->createRateSetting([
            'is_active' => true,
        ]);

        $this->post('/loading-practice-quotes', [
            'customer_name' => 'Amber Vale Eventing',
            'travel_miles' => 24,
            'on_site_hours' => '1.00',
            'handling_livery_period' => '',
            'handling_livery_quantity' => '',
            'manual_final_total' => '',
            'manual_final_total_reason' => '',
            'quote_notes' => '',
        ]);

        $quote = LoadingPracticeQuote::query()->firstOrFail();
        $quote->forceFill([
            'rate_setting_id' => null,
        ])->save();

        $this->get("/loading-practice-quotes/{$quote->id}")
            ->assertOk()
            ->assertSeeText('This loading-practice quote is missing its stored rate setting and cannot be repriced until that record is restored.')
            ->assertSeeText('No rate setting record has been resolved for this loading-practice quote yet.');
    }

    public function test_loading_practice_quote_creation_rolls_back_without_an_active_rate_setting(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from('/loading-practice-quotes/create')
            ->post('/loading-practice-quotes', [
                'customer_name' => 'Amber Vale Eventing',
                'travel_miles' => 24,
                'on_site_hours' => '1.00',
                'handling_livery_period' => '',
                'handling_livery_quantity' => '',
                'manual_final_total' => '',
                'manual_final_total_reason' => '',
                'quote_notes' => '',
            ])
            ->assertRedirect('/loading-practice-quotes/create')
            ->assertSessionHasErrors('pricing');

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('loading_practice_quotes', 0);
    }

    public function test_loading_practice_creation_screen_explains_when_the_rate_setting_is_missing(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get('/loading-practice-quotes/create');

        $response->assertOk();
        $response->assertSeeText('Loading-practice quotes cannot be saved until one active rate setting is in place.');
        $response->assertSeeText('Add rate setting');
        $response->assertDontSeeText('Save loading-practice quote');
    }

    private function createRateSetting(array $overrides = []): RateSetting
    {
        return RateSetting::query()->create(array_merge([
            'name' => 'Initial internal rates',
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
            'is_active' => false,
            'effective_from' => '2026-08-10',
        ], $overrides));
    }
}
