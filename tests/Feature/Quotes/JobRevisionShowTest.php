<?php

namespace Tests\Feature\Quotes;

use App\Models\Customer;
use App\Models\FuelPriceSource;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteLeg;
use App\Models\User;
use App\Models\WeeklyFuelPrice;
use App\Services\Pricing\JobRevisionPricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRevisionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_revision_screen_shows_the_resolved_active_pricing_records(): void
    {
        $this->actingAs(User::factory()->create());

        FuelPriceSource::factory()->texaco()->create();
        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
            'activated_at' => now(),
        ]);
        RateSetting::query()->create([
            'name' => 'Initial internal rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
            'effective_from' => '2026-08-10',
        ]);

        $revision = $this->createRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        app(JobRevisionPricingEngine::class)->price($revision);

        $response = $this->get("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $response->assertOk();
        $response->assertSeeText('Weekly fuel used for this quote');
        $response->assertSeeText('10 Aug 2026');
        $response->assertSeeText('Texaco (manual entry)');
        $response->assertDontSeeText('manual_texaco_entry');
        $response->assertSeeText('£1.5300');
        $response->assertSeeText('Rate setting used for this quote');
        $response->assertSeeText('Initial internal rates');
        $response->assertSeeText('EX16 0AA');
    }

    public function test_quote_revision_screen_shows_revision_specific_pricing_records_when_they_are_not_the_current_active_defaults(): void
    {
        $this->actingAs(User::factory()->create());

        $storedFuel = WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => false,
        ]);
        $storedRate = RateSetting::query()->create([
            'name' => 'Stored quote rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => false,
            'effective_from' => '2026-08-10',
        ]);
        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-17',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5700',
            'is_active' => true,
            'activated_at' => now(),
        ]);
        RateSetting::query()->create([
            'name' => 'Current active rates',
            'depot_postcode' => 'EX16 5PQ',
            'miles_per_gallon' => '22.5000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.060000',
            'unloaded_add_on_per_mile' => '0.605556',
            'loaded_add_on_per_mile' => '0.856452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.180000',
            'is_active' => true,
            'effective_from' => '2026-08-17',
        ]);

        $revision = $this->createRevision([
            'weekly_fuel_price_id' => $storedFuel->id,
            'rate_setting_id' => $storedRate->id,
        ]);
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        app(JobRevisionPricingEngine::class)->price($revision);

        $response = $this->get("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $response->assertOk();
        $response->assertSeeText('Stored on this revision');
        $response->assertSeeText('Stored quote rates');
        $response->assertSeeText('10 Aug 2026');
        $response->assertDontSeeText('Current active rates');
        $response->assertSeeText('Corrected weekly fuel price');
        $response->assertSeeText('17 Aug 2026');
    }

    public function test_unpriced_quote_revision_screen_marks_active_defaults_as_available_for_pricing(): void
    {
        $this->actingAs(User::factory()->create());

        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
            'activated_at' => now(),
        ]);
        RateSetting::query()->create([
            'name' => 'Initial internal rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
            'effective_from' => '2026-08-10',
        ]);

        $revision = $this->createRevision();

        $response = $this->get("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $response->assertOk();
        $response->assertSeeText('Current weekly fuel default for pricing');
        $response->assertSeeText('This revision has not been priced yet.');
        $response->assertSeeText('Current rate setting default for pricing');
        $response->assertDontSeeText('Weekly fuel used for this quote');
        $response->assertDontSeeText('Rate setting used for this quote');
    }

    public function test_non_draft_quote_revision_screen_disables_workspace_inputs(): void
    {
        $this->actingAs(User::factory()->create());

        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
            'activated_at' => now(),
        ]);
        RateSetting::query()->create([
            'name' => 'Initial internal rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
            'effective_from' => '2026-08-10',
        ]);

        $revision = $this->createRevision([
            'horse_count' => 2,
            'notes' => 'Needs evening drop-off',
        ], [
            'status' => 'pending',
        ]);
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        app(JobRevisionPricingEngine::class)->price($revision);

        $response = $this->get("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $response->assertOk();
        $response->assertSeeText('Return this quote to draft before saving changes to customer details, route miles, or pricing inputs.');
        $response->assertDontSeeText('Save quote workspace');
        $response->assertSee('fieldset class="workspace-fields" disabled', false);
    }

    public function test_quote_revision_screen_shows_revision_history_and_reporting_dates(): void
    {
        $this->actingAs(User::factory()->create());

        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
            'activated_at' => now(),
        ]);
        RateSetting::query()->create([
            'name' => 'Initial internal rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
            'effective_from' => '2026-08-10',
        ]);

        $firstRevision = $this->createRevision([
            'horse_count' => 1,
            'notes' => 'Original customer brief',
            'final_total' => '203.15',
            'engine_total' => '203.15',
        ]);
        $secondRevision = $this->createRevision([
            'job_id' => $firstRevision->job_id,
            'revision_number' => 2,
            'horse_count' => 2,
            'notes' => 'Booked customer brief',
            'final_total' => '240.00',
            'engine_total' => '202.83',
        ]);

        $firstRevision->job()->update([
            'current_working_revision_id' => $secondRevision->id,
            'issued_revision_id' => $firstRevision->id,
            'accepted_revision_id' => $secondRevision->id,
            'status' => 'booked',
            'issued_at' => now()->subDays(3),
            'booked_at' => now()->subDay(),
        ]);

        $response = $this->get("/jobs/{$firstRevision->job_id}/revisions/{$secondRevision->id}");

        $response->assertOk();
        $response->assertSeeText('Revision history');
        $response->assertSeeText('Revision 1');
        $response->assertSeeText('Revision 2');
        $response->assertSeeText('Issued revision');
        $response->assertSeeText('Accepted revision');
        $response->assertSeeText('Current working revision');
        $response->assertSeeText('Issued date');
        $response->assertSeeText('Booked date');
        $response->assertSeeText('Not completed yet');
    }

    public function test_quote_revision_screen_shows_leg_arithmetic_and_override_details_in_the_calculation_explanation(): void
    {
        $this->actingAs(User::factory()->create());

        WeeklyFuelPrice::query()->create([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => true,
            'activated_at' => now(),
        ]);
        RateSetting::query()->create([
            'name' => 'Initial internal rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
            'effective_from' => '2026-08-10',
        ]);

        $revision = $this->createRevision([
            'final_total' => '240.00',
            'manual_final_total_reason' => 'Evening surcharge agreed',
        ]);
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        app(JobRevisionPricingEngine::class)->price($revision, '240.00');

        $response = $this->get("/jobs/{$revision->job_id}/revisions/{$revision->id}");

        $response->assertOk();
        $response->assertSeeText('Leg calculations');
        $response->assertSeeText('10 miles at £0.921292');
        $response->assertSeeText('Base loaded rate per mile');
        $response->assertSeeText('£1.172188');
        $response->assertSeeText('Horse-adjusted loaded rate per mile');
        $response->assertSeeText('90 miles at £1.758282');
        $response->assertSeeText('1 at 1.500000');
        $response->assertSeeText('Manual final total override');
        $response->assertSeeText('Evening surcharge agreed');
    }

    private function createRevision(array $revisionOverrides = [], array $jobOverrides = []): JobRevision
    {
        $customer = Customer::query()->create([
            'name' => 'South West Equine Customer',
        ]);

        $job = Job::query()->create(array_merge([
            'customer_id' => $customer->id,
            'status' => 'draft',
        ], $jobOverrides));

        $revision = JobRevision::query()->create(array_merge([
            'job_id' => $job->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
        ], $revisionOverrides));

        Job::query()->whereKey($revision->job_id)->update([
            'current_working_revision_id' => $revision->id,
        ]);

        return $revision;
    }

    private function createStandardRouteLegs(JobRevision $revision, array $miles): void
    {
        $definitions = [
            ['sequence' => 1, 'label' => 'depot_to_pickup', 'rate_type' => 'unloaded'],
            ['sequence' => 2, 'label' => 'pickup_to_dropoff', 'rate_type' => 'loaded'],
            ['sequence' => 3, 'label' => 'dropoff_to_depot', 'rate_type' => 'unloaded'],
        ];

        foreach ($definitions as $index => $definition) {
            RouteLeg::query()->create([
                'job_revision_id' => $revision->id,
                'sequence' => $definition['sequence'],
                'label' => $definition['label'],
                'miles' => $miles[$index],
                'rate_type' => $definition['rate_type'],
            ]);
        }
    }
}
