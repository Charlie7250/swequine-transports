<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

        foreach ($jobColumns as $column) {
            $this->assertTrue(Schema::hasColumn('jobs', $column), "Expected [jobs.{$column}] column to exist.");
        }

        foreach ($revisionColumns as $column) {
            $this->assertTrue(Schema::hasColumn('job_revisions', $column), "Expected [job_revisions.{$column}] column to exist.");
        }
    }
}
