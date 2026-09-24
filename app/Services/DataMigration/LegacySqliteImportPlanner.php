<?php

namespace App\Services\DataMigration;

use JsonException;
use PDO;
use UnexpectedValueException;

class LegacySqliteImportPlanner
{
    private const TABLES = [
        'users',
        'weekly_fuel_prices',
        'rate_settings',
        'customers',
        'jobs',
        'job_revisions',
        'route_legs',
    ];

    private const IGNORED_TABLES = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'migrations',
        'password_reset_tokens',
        'queue_jobs',
        'sessions',
    ];

    private const EMPTY_TABLES = [
        'loading_practice_quotes',
        'shared_run_allocations',
        'shared_runs',
    ];

    private const COLUMNS = [
        'users' => ['id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'],
        'customers' => ['id', 'name', 'contact_name', 'email', 'phone', 'postcode', 'notes', 'created_at', 'updated_at'],
        'jobs' => ['id', 'customer_id', 'status', 'current_working_revision_id', 'issued_revision_id', 'accepted_revision_id', 'issued_at', 'booked_at', 'completed_at', 'lost_at', 'internal_notes', 'created_at', 'updated_at'],
        'weekly_fuel_prices' => ['id', 'week_commencing', 'source', 'price_per_litre_inc_vat', 'is_active', 'activated_at', 'created_at', 'updated_at'],
        'rate_settings' => ['id', 'name', 'depot_postcode', 'miles_per_gallon', 'litres_per_gallon', 'maintenance_per_mile', 'unloaded_add_on_per_mile', 'loaded_add_on_per_mile', 'two_horse_multiplier', 'is_active', 'effective_from', 'effective_until', 'created_at', 'updated_at'],
        'job_revisions' => ['id', 'job_id', 'weekly_fuel_price_id', 'rate_setting_id', 'revision_number', 'horse_count', 'depot_postcode_override', 'pickup_postcode', 'dropoff_postcode', 'notes', 'engine_total', 'final_total', 'manual_final_total_reason', 'calculation_explanation', 'created_at', 'updated_at'],
        'route_legs' => ['id', 'job_revision_id', 'sequence', 'label', 'start_postcode', 'end_postcode', 'miles', 'manual_miles', 'rate_type', 'rate_per_mile', 'amount', 'created_at', 'updated_at'],
    ];

    private const JOB_STATUSES = ['draft', 'quoted', 'pending', 'booked', 'completed', 'lost'];

    public function plan(string $sourcePath): array
    {
        $database = $this->connect($sourcePath);
        $schema = $this->validateSchema($database);
        $rows = [];

        foreach (self::TABLES as $table) {
            $rows[$table] = $this->readTable($database, $table);
        }

        $rows['users'] = array_map($this->normaliseUser(...), $rows['users']);
        $rows['rate_settings'] = array_map($this->transformRate(...), $rows['rate_settings']);
        $this->validateRows($rows);

        return [
            'source_checksum' => hash_file('sha256', $sourcePath),
            'source_schema_fingerprint' => hash('sha256', json_encode($schema, JSON_THROW_ON_ERROR)),
            'rows' => $rows,
            'counts' => array_map('count', $rows),
        ];
    }

    private function connect(string $sourcePath): PDO
    {
        $database = new PDO('sqlite:'.$sourcePath);
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->exec('PRAGMA query_only = ON');

        return $database;
    }

    private function readTable(PDO $database, string $table): array
    {
        return $database->query("SELECT * FROM {$table} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function validateSchema(PDO $database): array
    {
        $tables = $this->tableNames($database);
        $known = array_merge(self::TABLES, self::IGNORED_TABLES, self::EMPTY_TABLES);
        $unknown = array_values(array_diff($tables, $known));

        if ($unknown !== []) {
            throw new UnexpectedValueException('Unsupported legacy table: '.$unknown[0]);
        }

        $this->validateEmptyTables($database, $tables);

        return $this->schemaColumns($database);
    }

    private function tableNames(PDO $database): array
    {
        $statement = $database->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    private function validateEmptyTables(PDO $database, array $tables): void
    {
        foreach (array_intersect(self::EMPTY_TABLES, $tables) as $table) {
            if ((int) $database->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() > 0) {
                throw new UnexpectedValueException("Unsupported legacy data in table: {$table}");
            }
        }
    }

    private function schemaColumns(PDO $database): array
    {
        $schema = [];

        foreach (self::COLUMNS as $table => $expected) {
            $actual = $database->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC);
            $schema[$table] = array_column($actual, 'name');
            $this->assertColumns($table, $expected, $schema[$table]);
        }

        return $schema;
    }

    private function assertColumns(string $table, array $expected, array $actual): void
    {
        if ($actual !== $expected) {
            throw new UnexpectedValueException("Unsupported legacy schema for table: {$table}");
        }
    }

    private function validateRows(array $rows): void
    {
        $this->validateUsers($rows['users']);
        $this->validateFuel($rows['weekly_fuel_prices']);
        $this->validateRates($rows['rate_settings']);
        $this->validateJobs($rows['jobs'], $rows['customers'], $rows['job_revisions']);
        $this->validateRevisions($rows);
        $this->validateRouteLegs($rows['route_legs'], $rows['job_revisions']);
    }

    private function validateUsers(array $users): void
    {
        foreach ($users as $user) {
            if (preg_match('/[^\x00-\x7F]/', $user['normalised_email']) === 1) {
                throw new UnexpectedValueException("Legacy user {$user['source_id']} has a non-ASCII email address.");
            }
        }
    }

    private function validateFuel(array $fuelRows): void
    {
        if (count($fuelRows) !== 1 || ! $this->isApprovedFuelRow($fuelRows[0])) {
            throw new UnexpectedValueException('The legacy weekly fuel row does not match the approved invariant.');
        }
    }

    private function validateRates(array $rates): void
    {
        if (count($rates) !== 1 || $rates[0]['two_horse_multiplier'] !== '1.150000') {
            throw new UnexpectedValueException('The legacy historical rate must use a 1.15 two-horse multiplier.');
        }
    }

    private function isApprovedFuelRow(array $fuel): bool
    {
        return substr($fuel['week_commencing'], 0, 10) === '2026-08-10'
            && $fuel['source'] === 'manual_texaco_entry'
            && number_format((float) $fuel['price_per_litre_inc_vat'], 4, '.', '') === '1.5300'
            && (bool) $fuel['is_active'];
    }

    private function validateJobs(array $jobs, array $customers, array $revisions): void
    {
        $customerIds = $this->idSet($customers);
        $revisionsById = array_column($revisions, null, 'id');

        foreach ($jobs as $job) {
            if (! in_array($job['status'], self::JOB_STATUSES, true)) {
                throw new UnexpectedValueException("Invalid job status for job {$job['id']}.");
            }
            if (! isset($customerIds[$job['customer_id']])) {
                throw new UnexpectedValueException("Unresolved customer {$job['customer_id']} for job {$job['id']}.");
            }
            $this->validateJobPointers($job, $revisionsById);
        }
    }

    private function validateJobPointers(array $job, array $revisionsById): void
    {
        foreach (['current_working_revision_id', 'issued_revision_id', 'accepted_revision_id'] as $field) {
            $revisionId = $job[$field];
            if ($revisionId !== null && (! isset($revisionsById[$revisionId]) || (int) $revisionsById[$revisionId]['job_id'] !== (int) $job['id'])) {
                throw new UnexpectedValueException("Unresolved {$field} for job {$job['id']}.");
            }
        }
    }

    private function validateRevisions(array $rows): void
    {
        $jobs = $this->idSet($rows['jobs']);
        $fuel = $this->idSet($rows['weekly_fuel_prices']);
        $rates = $this->idSet($rows['rate_settings']);

        foreach ($rows['job_revisions'] as $revision) {
            $this->assertRevisionRelationships($revision, $jobs, $fuel, $rates);
            $this->decodeCalculation($revision);
        }
    }

    private function assertRevisionRelationships(array $revision, array $jobs, array $fuel, array $rates): void
    {
        if (! isset($jobs[$revision['job_id']])) {
            throw new UnexpectedValueException("Unresolved job for revision {$revision['id']}.");
        }
        if ($revision['weekly_fuel_price_id'] !== null && ! isset($fuel[$revision['weekly_fuel_price_id']])) {
            throw new UnexpectedValueException("Unresolved fuel price for revision {$revision['id']}.");
        }
        if ($revision['rate_setting_id'] !== null && ! isset($rates[$revision['rate_setting_id']])) {
            throw new UnexpectedValueException("Unresolved rate setting for revision {$revision['id']}.");
        }
    }

    private function decodeCalculation(array $revision): void
    {
        if ($revision['calculation_explanation'] === null) {
            return;
        }

        try {
            json_decode($revision['calculation_explanation'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new UnexpectedValueException("Malformed calculation JSON for job revision {$revision['id']}.");
        }
    }

    private function validateRouteLegs(array $routeLegs, array $revisions): void
    {
        $revisionIds = $this->idSet($revisions);
        $sequences = [];

        foreach ($routeLegs as $routeLeg) {
            if (! isset($revisionIds[$routeLeg['job_revision_id']])) {
                throw new UnexpectedValueException("Unresolved job revision for route leg {$routeLeg['id']}.");
            }
            $key = $routeLeg['job_revision_id'].':'.$routeLeg['sequence'];
            if (isset($sequences[$key])) {
                throw new UnexpectedValueException("Duplicate route sequence {$routeLeg['sequence']} for job revision {$routeLeg['job_revision_id']}.");
            }
            $sequences[$key] = true;
        }
    }

    private function idSet(array $rows): array
    {
        return array_fill_keys(array_column($rows, 'id'), true);
    }

    private function normaliseUser(array $user): array
    {
        return [
            'source_id' => (int) $user['id'],
            'normalised_email' => strtolower(trim($user['email'])),
        ];
    }

    private function transformRate(array $rate): array
    {
        return array_merge($rate, [
            'name' => $rate['name'].' [legacy-'.$rate['id'].']',
            'one_horse_multiplier' => '1.000000',
            'two_horse_multiplier' => number_format((float) $rate['two_horse_multiplier'], 6, '.', ''),
            'shared_load_percentage' => '0.750000',
            'loading_practice_within_15_miles_price' => '30.00',
            'loading_practice_within_25_miles_price' => '45.00',
            'loading_practice_within_50_miles_price' => '90.00',
            'loading_practice_on_site_hourly_rate' => '25.00',
            'loading_practice_livery_day_rate' => '35.00',
            'loading_practice_livery_week_rate' => '220.00',
            'loading_practice_livery_fortnight_rate' => '410.00',
            'is_active' => false,
        ]);
    }
}
