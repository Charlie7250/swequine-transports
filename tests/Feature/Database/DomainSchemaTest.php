<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_two_domain_tables_exist(): void
    {
        $tables = [
            'customers',
            'jobs',
            'job_revisions',
            'queue_jobs',
            'weekly_fuel_prices',
            'rate_settings',
            'route_legs',
            'shared_runs',
            'shared_run_allocations',
            'loading_practice_quotes',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected [{$table}] table to exist.");
        }
    }

    public function test_transport_enquiry_route_audit_tables_exist(): void
    {
        foreach (['transport_enquiries', 'route_resolutions', 'route_resolution_legs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected [{$table}] table to exist.");
        }

        $transportEnquiryColumns = [
            'source',
            'customer_name',
            'email',
            'phone',
            'pickup_postcode',
            'dropoff_postcode',
            'horse_count',
            'requested_date',
            'date_to_be_arranged',
            'special_constraints',
            'special_constraints_acknowledged',
            'status',
            'quote_job_id',
        ];

        $routeResolutionColumns = [
            'transport_enquiry_id',
            'provider',
            'provider_product',
            'request_id',
            'route_profile',
            'overall_status',
            'operator_action_required',
            'pricing_eligible',
            'raw_input_snapshot',
            'normalisation_metadata',
            'provider_metadata',
            'warnings',
            'failure_detail',
            'resolved_at',
            'attempted_at',
        ];

        $routeResolutionLegColumns = [
            'route_resolution_id',
            'sequence',
            'leg_type',
            'origin_input',
            'destination_input',
            'resolved_origin_metadata',
            'resolved_destination_metadata',
            'status',
            'distance_metres',
            'quoted_miles',
            'duration_seconds',
            'provider_route_id',
            'warnings',
            'failure_detail',
        ];

        foreach ($transportEnquiryColumns as $column) {
            $this->assertTrue(Schema::hasColumn('transport_enquiries', $column), "Expected [transport_enquiries.{$column}] column to exist.");
        }

        foreach ($routeResolutionColumns as $column) {
            $this->assertTrue(Schema::hasColumn('route_resolutions', $column), "Expected [route_resolutions.{$column}] column to exist.");
        }

        foreach ($routeResolutionLegColumns as $column) {
            $this->assertTrue(Schema::hasColumn('route_resolution_legs', $column), "Expected [route_resolution_legs.{$column}] column to exist.");
        }

        $this->assertTrue(Schema::hasColumn('job_revisions', 'route_resolution_id'));
    }

    public function test_transport_domain_foreign_keys_and_leg_sequence_constraint_exist(): void
    {
        $foreignKey = static function (string $table, string $column, string $referencedTable): bool {
            return collect(Schema::getForeignKeys($table))->contains(
                fn (array $key): bool => $key['columns'] === [$column]
                    && $key['foreign_table'] === $referencedTable,
            );
        };

        $this->assertTrue($foreignKey('transport_enquiries', 'quote_job_id', 'jobs'));
        $this->assertTrue($foreignKey('route_resolutions', 'transport_enquiry_id', 'transport_enquiries'));
        $this->assertTrue($foreignKey('route_resolution_legs', 'route_resolution_id', 'route_resolutions'));
        $this->assertTrue($foreignKey('job_revisions', 'route_resolution_id', 'route_resolutions'));

        $this->assertTrue(collect(Schema::getIndexes('route_resolution_legs'))->contains(
            fn (array $index): bool => $index['columns'] === ['route_resolution_id', 'sequence']
                && $index['unique'],
        ));
    }

    public function test_jobs_and_revisions_support_the_approved_relationship_markers(): void
    {
        $jobColumns = [
            'customer_id',
            'status',
            'current_working_revision_id',
            'issued_revision_id',
            'accepted_revision_id',
            'issued_at',
            'booked_at',
            'completed_at',
        ];

        $revisionColumns = [
            'job_id',
            'revision_number',
            'weekly_fuel_price_id',
            'rate_setting_id',
            'engine_total',
            'final_total',
            'calculation_explanation',
        ];

        $rateSettingColumns = [
            'one_horse_multiplier',
            'shared_load_percentage',
            'loading_practice_within_15_miles_price',
            'loading_practice_within_25_miles_price',
            'loading_practice_within_50_miles_price',
            'loading_practice_on_site_hourly_rate',
            'loading_practice_livery_day_rate',
            'loading_practice_livery_week_rate',
            'loading_practice_livery_fortnight_rate',
        ];

        $loadingPracticeQuoteColumns = [
            'rate_setting_id',
            'distance_band',
            'travel_miles',
            'package_price',
            'on_site_hours',
            'on_site_hourly_rate',
            'handling_livery_period',
            'handling_livery_quantity',
            'handling_livery_rate',
            'is_poa',
            'engine_total',
            'final_total',
            'manual_final_total_reason',
            'calculation_explanation',
        ];

        foreach ($jobColumns as $column) {
            $this->assertTrue(Schema::hasColumn('jobs', $column), "Expected [jobs.{$column}] column to exist.");
        }

        foreach ($revisionColumns as $column) {
            $this->assertTrue(Schema::hasColumn('job_revisions', $column), "Expected [job_revisions.{$column}] column to exist.");
        }

        foreach ($rateSettingColumns as $column) {
            $this->assertTrue(Schema::hasColumn('rate_settings', $column), "Expected [rate_settings.{$column}] column to exist.");
        }

        foreach ($loadingPracticeQuoteColumns as $column) {
            $this->assertTrue(Schema::hasColumn('loading_practice_quotes', $column), "Expected [loading_practice_quotes.{$column}] column to exist.");
        }
    }

    public function test_one_horse_multiplier_migration_preserves_existing_rate_settings(): void
    {
        $migration = require database_path('migrations/2026_09_13_000010_add_one_horse_multiplier_to_rate_settings_table.php');
        $migration->down();

        $identifier = DB::table('rate_settings')->insertGetId([
            'name' => 'Existing historical rates',
            'depot_postcode' => 'EX16 0AA',
            'miles_per_gallon' => '22.0000',
            'litres_per_gallon' => '4.5400',
            'maintenance_per_mile' => '0.050000',
            'unloaded_add_on_per_mile' => '0.555556',
            'loaded_add_on_per_mile' => '0.806452',
            'two_horse_multiplier' => '1.150000',
            'is_active' => true,
            'effective_from' => '2026-08-17',
            'created_at' => '2026-08-17 09:00:00',
            'updated_at' => '2026-08-17 09:00:00',
        ]);

        $migration->up();

        $rateSetting = DB::table('rate_settings')->where('id', $identifier)->first();

        $this->assertSame($identifier, $rateSetting->id);
        $this->assertSame('Existing historical rates', $rateSetting->name);
        $this->assertSame(1.15, $rateSetting->two_horse_multiplier);
        $this->assertNull($rateSetting->one_horse_multiplier);
        $this->assertSame('2026-08-17', $rateSetting->effective_from);
    }
}
