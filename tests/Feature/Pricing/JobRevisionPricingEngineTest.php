<?php

namespace Tests\Feature\Pricing;

use App\Models\Customer;
use App\Models\Job;
use App\Models\JobRevision;
use App\Models\RateSetting;
use App\Models\RouteLeg;
use App\Models\SharedRun;
use App\Models\SharedRunAllocation;
use App\Models\WeeklyFuelPrice;
use App\Services\Pricing\JobRevisionPricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class JobRevisionPricingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_revision_pricing_uses_the_stored_one_horse_multiplier(): void
    {
        $this->createWeeklyFuelPrice(['is_active' => true]);
        $this->createRateSetting(['is_active' => true]);
        $revision = $this->createJobRevision();
        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        try {
            $this->pricingEngine()->price($revision);
        } catch (InvalidArgumentException $exception) {
            $this->fail($exception->getMessage());
        }

        $revision->refresh();

        $this->assertSame('255.90', $revision->engine_total);
        $this->assertSame('1.500000', $revision->calculation_explanation['horse_count']['multiplier']);
    }

    public function test_it_prices_a_job_revision_from_stored_route_legs_and_persists_the_explanation_payload(): void
    {
        $fuelPrice = $this->createWeeklyFuelPrice([
            'is_active' => true,
        ]);
        $rateSetting = $this->createRateSetting([
            'is_active' => true,
        ]);
        $revision = $this->createJobRevision();

        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->pricingEngine()->price($revision);

        $revision->refresh();
        $legs = $revision->routeLegs()->orderBy('sequence')->get()->values();

        $this->assertSame($fuelPrice->id, $revision->weekly_fuel_price_id);
        $this->assertSame($rateSetting->id, $revision->rate_setting_id);
        $this->assertSame('255.90', $revision->engine_total);
        $this->assertSame('255.90', $revision->final_total);
        $this->assertSame('manual_texaco_entry', $revision->calculation_explanation['fuel_context']['source']);
        $this->assertSame('1.5300', $revision->calculation_explanation['fuel_context']['price_per_litre_inc_vat']);
        $this->assertSame('0.921292', $revision->calculation_explanation['resolved_rates']['unloaded_rate_per_mile']);
        $this->assertSame('1.172188', $revision->calculation_explanation['resolved_rates']['base_loaded_rate_per_mile']);
        $this->assertSame('1.758282', $revision->calculation_explanation['resolved_rates']['loaded_rate_per_mile']);
        $this->assertSame('255.90', $revision->calculation_explanation['engine_total']);
        $this->assertSame('255.90', $revision->calculation_explanation['final_total']);
        $this->assertSame('0.921292', $legs[0]->rate_per_mile);
        $this->assertSame('9.21', $legs[0]->amount);
        $this->assertSame('1.758282', $legs[1]->rate_per_mile);
        $this->assertSame('158.25', $legs[1]->amount);
        $this->assertSame('0.921292', $legs[2]->rate_per_mile);
        $this->assertSame('88.44', $legs[2]->amount);
    }

    public function test_it_uses_revision_specific_pricing_records_instead_of_the_current_active_records(): void
    {
        $assignedFuelPrice = $this->createWeeklyFuelPrice();
        $assignedRateSetting = $this->createRateSetting();

        $this->createWeeklyFuelPrice([
            'week_commencing' => '2026-08-17',
            'price_per_litre_inc_vat' => '1.8800',
            'is_active' => true,
        ]);
        $this->createRateSetting([
            'name' => 'Current active rates',
            'maintenance_per_mile' => '0.250000',
            'unloaded_add_on_per_mile' => '0.655556',
            'loaded_add_on_per_mile' => '1.006452',
            'one_horse_multiplier' => '1.500000',
            'is_active' => true,
        ]);

        $revision = $this->createJobRevision([
            'weekly_fuel_price_id' => $assignedFuelPrice->id,
            'rate_setting_id' => $assignedRateSetting->id,
        ]);

        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->pricingEngine()->price($revision);

        $revision->refresh();

        $this->assertSame($assignedFuelPrice->id, $revision->weekly_fuel_price_id);
        $this->assertSame($assignedRateSetting->id, $revision->rate_setting_id);
        $this->assertSame('1.5300', $revision->calculation_explanation['fuel_context']['price_per_litre_inc_vat']);
        $this->assertSame('0.050000', $revision->calculation_explanation['rate_inputs']['maintenance_per_mile']);
        $this->assertSame('255.90', $revision->engine_total);
    }

    public function test_general_repricing_clears_a_stored_manual_final_total_override(): void
    {
        $this->createWeeklyFuelPrice([
            'is_active' => true,
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $revision = $this->createJobRevision([
            'final_total' => '215.00',
            'manual_final_total_reason' => 'Customer loyalty adjustment',
        ]);

        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->pricingEngine()->price($revision);

        $revision->refresh();

        $this->assertSame('255.90', $revision->engine_total);
        $this->assertSame('255.90', $revision->final_total);
        $this->assertSame('255.90', $revision->calculation_explanation['engine_total']);
        $this->assertSame('255.90', $revision->calculation_explanation['final_total']);
        $this->assertSame([], $revision->calculation_explanation['overrides']);
        $this->assertNull($revision->manual_final_total_reason);
    }

    public function test_general_repricing_clears_a_stored_final_total_without_override_evidence(): void
    {
        $this->createWeeklyFuelPrice([
            'is_active' => true,
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $revision = $this->createJobRevision([
            'engine_total' => '0.00',
            'final_total' => '215.00',
            'manual_final_total_reason' => null,
        ]);

        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->pricingEngine()->price($revision);

        $revision->refresh();

        $this->assertSame('255.90', $revision->engine_total);
        $this->assertSame('255.90', $revision->final_total);
        $this->assertSame('255.90', $revision->calculation_explanation['engine_total']);
        $this->assertSame('255.90', $revision->calculation_explanation['final_total']);
        $this->assertSame([], $revision->calculation_explanation['overrides']);
    }

    public function test_it_uses_the_stored_shared_load_percentage_for_shared_loaded_miles(): void
    {
        $this->createWeeklyFuelPrice([
            'is_active' => true,
        ]);
        $rateSetting = $this->createRateSetting([
            'is_active' => true,
            'shared_load_percentage' => '0.850000',
        ]);
        $revision = $this->createJobRevision([
            'rate_setting_id' => $rateSetting->id,
        ]);

        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $sharedRun = SharedRun::query()->create([
            'name' => 'Configured shared load run',
        ]);

        SharedRunAllocation::query()->create([
            'shared_run_id' => $sharedRun->id,
            'job_revision_id' => $revision->id,
            'allocation_explanation' => [
                'type' => 'shared_run',
                'allocation_legs' => [
                    [
                        'label' => 'depot_to_pickup',
                        'full_miles' => 6,
                        'split_miles' => 4,
                        'split_divisor' => 2,
                        'reason' => 'First customer covers the solo approach, then shares the final approach into the collection point.',
                    ],
                    [
                        'label' => 'pickup_to_dropoff',
                        'full_miles' => 60,
                        'split_miles' => 30,
                        'split_divisor' => 2,
                        'reason' => 'The first sixty loaded miles belong only to this customer, the final thirty loaded miles are genuinely shared.',
                    ],
                    [
                        'label' => 'dropoff_to_depot',
                        'full_miles' => 96,
                        'split_miles' => 0,
                        'split_divisor' => null,
                        'reason' => 'The return to depot is this customer’s own section after the shared route ends.',
                    ],
                ],
            ],
        ]);

        $this->pricingEngine()->price($revision);

        $revision->refresh();
        $allocation = SharedRunAllocation::query()->where('job_revision_id', $revision->id)->firstOrFail();

        $this->assertSame('246.15', $revision->engine_total);
        $this->assertSame('0.850000', $revision->calculation_explanation['shared_load']['shared_load_percentage']);
        $this->assertSame('44.84', $revision->calculation_explanation['shared_load']['allocation_legs'][1]['split_amount']);
        $this->assertSame('0.850000', $allocation->allocation_explanation['shared_load_percentage']);
        $this->assertSame('0.850000', $allocation->allocation_explanation['allocation_legs'][1]['shared_load_percentage']);
    }

    public function test_it_rejects_revisions_that_are_missing_the_standard_three_route_legs(): void
    {
        $this->createWeeklyFuelPrice([
            'is_active' => true,
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);
        $revision = $this->createJobRevision();

        RouteLeg::query()->create([
            'job_revision_id' => $revision->id,
            'sequence' => 1,
            'label' => 'depot_to_pickup',
            'miles' => 10,
            'rate_type' => 'unloaded',
        ]);

        RouteLeg::query()->create([
            'job_revision_id' => $revision->id,
            'sequence' => 2,
            'label' => 'pickup_to_dropoff',
            'miles' => 90,
            'rate_type' => 'loaded',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Standard single quotes require exactly three route legs: depot_to_pickup, pickup_to_dropoff, and dropoff_to_depot.');

        $this->pricingEngine()->price($revision);
    }

    public function test_general_repricing_clears_a_manual_reason_without_an_explicit_override(): void
    {
        $this->createWeeklyFuelPrice([
            'is_active' => true,
        ]);
        $this->createRateSetting([
            'is_active' => true,
        ]);

        $revision = $this->createJobRevision([
            'final_total' => null,
            'manual_final_total_reason' => 'Needs follow-up approval',
        ]);

        $this->createStandardRouteLegs($revision, [10, 90, 96]);

        $this->pricingEngine()->price($revision);

        $revision->refresh();

        $this->assertSame('255.90', $revision->final_total);
        $this->assertNull($revision->manual_final_total_reason);
        $this->assertSame([], $revision->calculation_explanation['overrides']);
    }

    private function pricingEngine(): object
    {
        if (! class_exists(JobRevisionPricingEngine::class)) {
            $this->fail('Expected App\Services\Pricing\JobRevisionPricingEngine to exist.');
        }

        return app(JobRevisionPricingEngine::class);
    }

    private function createWeeklyFuelPrice(array $overrides = []): WeeklyFuelPrice
    {
        return WeeklyFuelPrice::query()->create(array_merge([
            'week_commencing' => '2026-08-10',
            'source' => 'manual_texaco_entry',
            'price_per_litre_inc_vat' => '1.5300',
            'is_active' => false,
            'activated_at' => null,
        ], $overrides));
    }

    private function createRateSetting(array $overrides = []): RateSetting
    {
        return RateSetting::query()->create(array_merge([
            'name' => 'Stored quote rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'one_horse_multiplier' => '1.500000',
            'shared_load_percentage' => '0.750000',
            'two_horse_multiplier' => '1.750000',
            'is_active' => false,
            'effective_from' => '2026-08-10',
        ], $overrides));
    }

    private function createJobRevision(array $overrides = []): JobRevision
    {
        $customer = Customer::query()->create([
            'name' => 'South West Equine Customer',
        ]);

        $job = Job::query()->create([
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        return JobRevision::query()->create(array_merge([
            'job_id' => $job->id,
            'revision_number' => 1,
            'horse_count' => 1,
            'pickup_postcode' => 'EX1 1AA',
            'dropoff_postcode' => 'TA1 1AA',
        ], $overrides));
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
