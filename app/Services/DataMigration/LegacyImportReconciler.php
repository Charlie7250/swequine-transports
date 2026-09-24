<?php

namespace App\Services\DataMigration;

use App\Models\LegacyImportRun;
use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacyImportReconciler
{
    public function __construct(private readonly LegacySqliteImportPlanner $planner) {}

    public function verify(LegacyImportRun $run): void
    {
        $plan = $this->planner->plan($run->source_snapshot_path);
        $mappings = $run->identifier_mappings ?? [];
        $this->assertMappingCompleteness($plan['rows'], $mappings);
        $this->verifyUsers($plan['rows']['users'], $mappings['users']);
        $this->verifyTable('weekly_fuel_prices', $plan['rows']['weekly_fuel_prices'], $mappings['weekly_fuel_prices'], $this->canonicalFuel(...), $this->canonicalFuel(...));
        $this->verifyTable('rate_settings', $plan['rows']['rate_settings'], $mappings['rate_settings'], $this->canonicalRate(...), $this->canonicalRate(...));
        $this->verifyTable('customers', $plan['rows']['customers'], $mappings['customers'], $this->canonicalCustomer(...), $this->canonicalCustomer(...));
        $this->verifyTable('jobs', $plan['rows']['jobs'], $mappings['jobs'], fn (array $row): array => $this->canonicalSourceJob($row, $mappings), $this->canonicalJob(...));
        $this->verifyTable('job_revisions', $plan['rows']['job_revisions'], $mappings['job_revisions'], fn (array $row): array => $this->canonicalSourceRevision($row, $mappings), $this->canonicalRevision(...));
        $this->verifyTable('route_legs', $plan['rows']['route_legs'], $mappings['route_legs'], fn (array $row): array => $this->canonicalSourceRouteLeg($row, $mappings), $this->canonicalRouteLeg(...));
    }

    private function assertMappingCompleteness(array $rows, array $mappings): void
    {
        $expectedTables = ['users', 'weekly_fuel_prices', 'rate_settings', 'customers', 'jobs', 'job_revisions', 'route_legs'];
        $actualTables = array_keys($mappings);
        sort($actualTables);
        $sortedExpected = $expectedTables;
        sort($sortedExpected);

        if ($actualTables !== $sortedExpected) {
            throw new RuntimeException('The identifier mapping tables are incomplete.');
        }

        foreach ($expectedTables as $table) {
            $sourceField = $table === 'users' ? 'source_id' : 'id';
            $expectedIds = array_map('strval', array_column($rows[$table], $sourceField));
            $actualIds = array_map('strval', array_keys($mappings[$table]));
            sort($expectedIds, SORT_NUMERIC);
            sort($actualIds, SORT_NUMERIC);
            if ($expectedIds !== $actualIds) {
                throw new RuntimeException("The identifier mapping for {$table} is incomplete.");
            }
        }
    }

    private function verifyUsers(array $users, array $mapping): void
    {
        foreach ($users as $user) {
            $target = DB::table('users')->find($mapping[(string) $user['source_id']]);
            $email = $target === null ? null : strtolower(trim($target->email));
            if ($email !== $user['normalised_email']) {
                throw new RuntimeException("Imported users row {$user['source_id']} does not match its source mapping.");
            }
        }
    }

    private function verifyTable(string $table, array $rows, array $mapping, Closure $expected, Closure $actual): void
    {
        foreach ($rows as $row) {
            $target = DB::table($table)->find($mapping[(string) $row['id']]);
            if ($target === null || $expected($row) !== $actual((array) $target)) {
                throw new RuntimeException("Imported {$table} row {$row['id']} does not match its source mapping.");
            }
        }
    }

    private function canonicalFuel(array $row): array
    {
        return [
            'week_commencing' => $this->date($row['week_commencing']),
            'source' => $row['source'],
            'price_per_litre_inc_vat' => $this->decimal($row['price_per_litre_inc_vat'], 4),
            'is_active' => (bool) $row['is_active'],
        ];
    }

    private function canonicalRate(array $row): array
    {
        return [
            'name' => $row['name'],
            'depot_postcode' => $row['depot_postcode'],
            'miles_per_gallon' => $this->decimal($row['miles_per_gallon'], 4),
            'litres_per_gallon' => $this->decimal($row['litres_per_gallon'], 4),
            'maintenance_per_mile' => $this->decimal($row['maintenance_per_mile'], 6),
            'unloaded_add_on_per_mile' => $this->decimal($row['unloaded_add_on_per_mile'], 6),
            'loaded_add_on_per_mile' => $this->decimal($row['loaded_add_on_per_mile'], 6),
            'one_horse_multiplier' => $this->decimal($row['one_horse_multiplier'], 6),
            'shared_load_percentage' => $this->decimal($row['shared_load_percentage'], 6),
            'two_horse_multiplier' => $this->decimal($row['two_horse_multiplier'], 6),
            'loading_practice_within_15_miles_price' => $this->decimal($row['loading_practice_within_15_miles_price'], 2),
            'loading_practice_within_25_miles_price' => $this->decimal($row['loading_practice_within_25_miles_price'], 2),
            'loading_practice_within_50_miles_price' => $this->decimal($row['loading_practice_within_50_miles_price'], 2),
            'loading_practice_on_site_hourly_rate' => $this->decimal($row['loading_practice_on_site_hourly_rate'], 2),
            'loading_practice_livery_day_rate' => $this->decimal($row['loading_practice_livery_day_rate'], 2),
            'loading_practice_livery_week_rate' => $this->decimal($row['loading_practice_livery_week_rate'], 2),
            'loading_practice_livery_fortnight_rate' => $this->decimal($row['loading_practice_livery_fortnight_rate'], 2),
            'is_active' => (bool) $row['is_active'],
            'effective_from' => $this->date($row['effective_from']),
            'effective_until' => $this->date($row['effective_until']),
            'created_at' => $this->timestamp($row['created_at']),
            'updated_at' => $this->timestamp($row['updated_at']),
        ];
    }

    private function canonicalCustomer(array $row): array
    {
        return $this->only($row, ['name', 'contact_name', 'email', 'phone', 'postcode', 'notes']) + [
            'created_at' => $this->timestamp($row['created_at']),
            'updated_at' => $this->timestamp($row['updated_at']),
        ];
    }

    private function canonicalSourceJob(array $row, array $mappings): array
    {
        $row['customer_id'] = $mappings['customers'][(string) $row['customer_id']];
        $row['transport_day_id'] = null;
        $row['transport_day_sequence'] = null;
        foreach (['current_working_revision_id', 'issued_revision_id', 'accepted_revision_id'] as $field) {
            $row[$field] = $this->mappedNullable($row[$field], $mappings['job_revisions']);
        }

        return $this->canonicalJob($row);
    }

    private function canonicalJob(array $row): array
    {
        return [
            'customer_id' => (int) $row['customer_id'],
            'transport_day_id' => $this->nullableInteger($row['transport_day_id']),
            'transport_day_sequence' => $this->nullableInteger($row['transport_day_sequence']),
            'status' => $row['status'],
            'current_working_revision_id' => $this->nullableInteger($row['current_working_revision_id']),
            'issued_revision_id' => $this->nullableInteger($row['issued_revision_id']),
            'accepted_revision_id' => $this->nullableInteger($row['accepted_revision_id']),
            'issued_at' => $this->timestamp($row['issued_at']),
            'booked_at' => $this->timestamp($row['booked_at']),
            'completed_at' => $this->timestamp($row['completed_at']),
            'lost_at' => $this->timestamp($row['lost_at']),
            'internal_notes' => $row['internal_notes'],
            'created_at' => $this->timestamp($row['created_at']),
            'updated_at' => $this->timestamp($row['updated_at']),
        ];
    }

    private function canonicalSourceRevision(array $row, array $mappings): array
    {
        $row['job_id'] = $mappings['jobs'][(string) $row['job_id']];
        $row['weekly_fuel_price_id'] = $this->mappedNullable($row['weekly_fuel_price_id'], $mappings['weekly_fuel_prices']);
        $row['rate_setting_id'] = $this->mappedNullable($row['rate_setting_id'], $mappings['rate_settings']);
        $row['route_resolution_id'] = null;
        $row['issued_at'] = null;
        $row['issued_by_user_id'] = null;
        $row['issue_reference'] = null;
        $row['issued_evidence'] = null;

        return $this->canonicalRevision($row);
    }

    private function canonicalRevision(array $row): array
    {
        return [
            'job_id' => (int) $row['job_id'],
            'weekly_fuel_price_id' => $this->nullableInteger($row['weekly_fuel_price_id']),
            'rate_setting_id' => $this->nullableInteger($row['rate_setting_id']),
            'route_resolution_id' => $this->nullableInteger($row['route_resolution_id']),
            'revision_number' => (int) $row['revision_number'],
            'horse_count' => (int) $row['horse_count'],
            'depot_postcode_override' => $row['depot_postcode_override'],
            'pickup_postcode' => $row['pickup_postcode'],
            'dropoff_postcode' => $row['dropoff_postcode'],
            'notes' => $row['notes'],
            'engine_total' => $this->decimal($row['engine_total'], 2),
            'final_total' => $this->nullableDecimal($row['final_total'], 2),
            'manual_final_total_reason' => $row['manual_final_total_reason'],
            'calculation_explanation' => $this->json($row['calculation_explanation']),
            'issued_at' => $this->timestamp($row['issued_at']),
            'issued_by_user_id' => $this->nullableInteger($row['issued_by_user_id']),
            'issue_reference' => $row['issue_reference'],
            'issued_evidence' => $this->json($row['issued_evidence']),
            'created_at' => $this->timestamp($row['created_at']),
            'updated_at' => $this->timestamp($row['updated_at']),
        ];
    }

    private function canonicalSourceRouteLeg(array $row, array $mappings): array
    {
        $row['job_revision_id'] = $mappings['job_revisions'][(string) $row['job_revision_id']];

        return $this->canonicalRouteLeg($row);
    }

    private function canonicalRouteLeg(array $row): array
    {
        return [
            'job_revision_id' => (int) $row['job_revision_id'],
            'sequence' => (int) $row['sequence'],
            'label' => $row['label'],
            'start_postcode' => $row['start_postcode'],
            'end_postcode' => $row['end_postcode'],
            'miles' => (int) $row['miles'],
            'manual_miles' => $this->nullableInteger($row['manual_miles']),
            'rate_type' => $row['rate_type'],
            'rate_per_mile' => $this->nullableDecimal($row['rate_per_mile'], 6),
            'amount' => $this->nullableDecimal($row['amount'], 2),
            'created_at' => $this->timestamp($row['created_at']),
            'updated_at' => $this->timestamp($row['updated_at']),
        ];
    }

    private function only(array $row, array $fields): array
    {
        return array_intersect_key($row, array_flip($fields));
    }

    private function mappedNullable(mixed $sourceId, array $mapping): ?int
    {
        return $sourceId === null ? null : (int) $mapping[(string) $sourceId];
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function decimal(mixed $value, int $scale): string
    {
        return number_format((float) $value, $scale, '.', '');
    }

    private function nullableDecimal(mixed $value, int $scale): ?string
    {
        return $value === null ? null : $this->decimal($value, $scale);
    }

    private function date(mixed $value): ?string
    {
        return $value === null ? null : substr((string) $value, 0, 10);
    }

    private function timestamp(mixed $value): ?string
    {
        return $value === null ? null : substr((string) $value, 0, 19);
    }

    private function json(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode(json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
