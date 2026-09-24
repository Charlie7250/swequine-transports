<?php

namespace App\Services\DataMigration;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

class LegacySqliteImporter
{
    public function preflight(array $plan): void
    {
        $this->mapUsers($plan['rows']['users']);
        $this->assertFuelSource();
        $this->assertFuelRows($plan['rows']['weekly_fuel_prices']);
        $this->assertRateRows($plan['rows']['rate_settings']);
    }

    public function import(array $plan, ?callable $beforeWrite = null, ?callable $afterWrite = null): array
    {
        $this->preflight($plan);

        return DB::transaction(function () use ($plan, $beforeWrite, $afterWrite): array {
            if ($beforeWrite !== null) {
                $beforeWrite();
            }

            $mappings = ['users' => $this->mapUsers($plan['rows']['users'])];
            $this->reconcileFuelSource();
            $mappings['weekly_fuel_prices'] = $this->importFuel($plan['rows']['weekly_fuel_prices']);
            $mappings['rate_settings'] = $this->importRates($plan['rows']['rate_settings']);
            $mappings['customers'] = $this->insertRows('customers', $plan['rows']['customers']);
            $mappings['jobs'] = $this->importJobs($plan['rows']['jobs'], $mappings);
            $mappings['job_revisions'] = $this->importRevisions($plan['rows']['job_revisions'], $mappings);
            $mappings['route_legs'] = $this->importRouteLegs($plan['rows']['route_legs'], $mappings);
            $this->restoreRevisionPointers($plan['rows']['jobs'], $mappings);

            $result = ['mappings' => $mappings, 'mapping_checksum' => $this->mappingChecksum($mappings)];
            if ($afterWrite !== null) {
                $afterWrite($result);
            }

            return $result;
        });
    }

    private function mapUsers(array $users): array
    {
        if (count($users) !== 1 || $users[0]['normalised_email'] !== 'ops@sweq.local') {
            throw new UnexpectedValueException('The legacy operations user must be ops@sweq.local.');
        }

        $mappings = [];

        foreach ($users as $user) {
            $targets = DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', [$user['normalised_email']])->get();
            if ($targets->isEmpty()) {
                throw new UnexpectedValueException("No PostgreSQL user matches legacy user {$user['source_id']}.");
            }
            if ($targets->count() > 1) {
                throw new UnexpectedValueException("Multiple PostgreSQL users match legacy user {$user['source_id']}.");
            }
            $target = $targets->first();
            $mappings[(string) $user['source_id']] = (int) $target->id;
        }

        return $mappings;
    }

    private function reconcileFuelSource(): void
    {
        $this->assertFuelSource();
        $byKey = DB::table('fuel_price_sources')->where('key', 'manual_texaco_entry')->first();

        if ($byKey === null) {
            DB::table('fuel_price_sources')->insert($this->timestamps([
                'key' => 'manual_texaco_entry',
                'display_name' => 'Texaco (manual entry)',
            ]));
        }
    }

    private function assertFuelSource(): void
    {
        $byKey = DB::table('fuel_price_sources')->where('key', 'manual_texaco_entry')->first();
        $byName = DB::table('fuel_price_sources')->where('display_name', 'Texaco (manual entry)')->first();

        if (($byKey !== null && $byKey->display_name !== 'Texaco (manual entry)') || ($byName !== null && $byName->key !== 'manual_texaco_entry')) {
            throw new UnexpectedValueException('The manual fuel source conflicts with existing PostgreSQL data.');
        }
    }

    private function assertFuelRows(array $rows): void
    {
        foreach ($rows as $row) {
            $matches = $this->matchingFuelRows($row);
            $this->assertSingleFuelMatch($matches);
            if ($matches->isNotEmpty()) {
                $this->reconcileFuel($matches->first(), $row);
            }
        }
    }

    private function assertRateRows(array $rows): void
    {
        foreach ($rows as $row) {
            if (DB::table('rate_settings')->where('name', $row['name'])->exists()) {
                throw new UnexpectedValueException("Historical rate name conflicts with existing PostgreSQL data: {$row['name']}");
            }
        }
    }

    private function importFuel(array $rows): array
    {
        $mappings = [];

        foreach ($rows as $row) {
            $matches = $this->matchingFuelRows($row);
            $this->assertSingleFuelMatch($matches);
            $targetId = $matches->isEmpty() ? $this->insertFuel($row) : $this->reconcileFuel($matches->first(), $row);
            $mappings[(string) $row['id']] = $targetId;
        }

        return $mappings;
    }

    private function matchingFuelRows(array $row): Collection
    {
        return DB::table('weekly_fuel_prices')
            ->where('week_commencing', substr($row['week_commencing'], 0, 10))
            ->where('source', $row['source'])
            ->get();
    }

    private function assertSingleFuelMatch(Collection $matches): void
    {
        if ($matches->count() > 1) {
            throw new UnexpectedValueException('Multiple PostgreSQL weekly fuel rows match the legacy fuel identity.');
        }
    }

    private function insertFuel(array $row): int
    {
        return (int) DB::table('weekly_fuel_prices')->insertGetId([
            'week_commencing' => substr($row['week_commencing'], 0, 10),
            'source' => $row['source'],
            'price_per_litre_inc_vat' => number_format((float) $row['price_per_litre_inc_vat'], 4, '.', ''),
            'is_active' => (bool) $row['is_active'],
            'activated_at' => $row['activated_at'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ]);
    }

    private function reconcileFuel(object $existing, array $row): int
    {
        $price = number_format((float) $existing->price_per_litre_inc_vat, 4, '.', '');

        if ($price !== '1.5300' || ! (bool) $existing->is_active) {
            throw new UnexpectedValueException('The legacy weekly fuel row conflicts with existing PostgreSQL data.');
        }

        return (int) $existing->id;
    }

    private function importRates(array $rows): array
    {
        $mappings = [];

        foreach ($rows as $row) {
            $existing = DB::table('rate_settings')->where('name', $row['name'])->first();
            if ($existing !== null) {
                throw new UnexpectedValueException("Historical rate name conflicts with existing PostgreSQL data: {$row['name']}");
            }
            $sourceId = (string) $row['id'];
            unset($row['id']);
            $row['effective_from'] = $row['effective_from'] === null ? null : substr($row['effective_from'], 0, 10);
            $mappings[$sourceId] = (int) DB::table('rate_settings')->insertGetId($row);
        }

        return $mappings;
    }

    private function insertRows(string $table, array $rows): array
    {
        $mappings = [];

        foreach ($rows as $row) {
            $sourceId = (string) $row['id'];
            unset($row['id']);
            $mappings[$sourceId] = (int) DB::table($table)->insertGetId($row);
        }

        return $mappings;
    }

    private function importJobs(array $rows, array $mappings): array
    {
        $jobMappings = [];

        foreach ($rows as $row) {
            $sourceId = (string) $row['id'];
            $row['customer_id'] = $mappings['customers'][(string) $row['customer_id']];
            $row['current_working_revision_id'] = null;
            $row['issued_revision_id'] = null;
            $row['accepted_revision_id'] = null;
            unset($row['id']);
            $jobMappings[$sourceId] = (int) DB::table('jobs')->insertGetId($row);
        }

        return $jobMappings;
    }

    private function importRevisions(array $rows, array $mappings): array
    {
        $revisionMappings = [];

        foreach ($rows as $row) {
            $sourceId = (string) $row['id'];
            $row['job_id'] = $mappings['jobs'][(string) $row['job_id']];
            $row['weekly_fuel_price_id'] = $this->mappedNullable($row['weekly_fuel_price_id'], $mappings['weekly_fuel_prices']);
            $row['rate_setting_id'] = $this->mappedNullable($row['rate_setting_id'], $mappings['rate_settings']);
            unset($row['id']);
            $revisionMappings[$sourceId] = (int) DB::table('job_revisions')->insertGetId($row);
        }

        return $revisionMappings;
    }

    private function importRouteLegs(array $rows, array $mappings): array
    {
        $routeMappings = [];

        foreach ($rows as $row) {
            $sourceId = (string) $row['id'];
            $row['job_revision_id'] = $mappings['job_revisions'][(string) $row['job_revision_id']];
            unset($row['id']);
            $routeMappings[$sourceId] = (int) DB::table('route_legs')->insertGetId($row);
        }

        return $routeMappings;
    }

    private function restoreRevisionPointers(array $jobs, array $mappings): void
    {
        foreach ($jobs as $job) {
            DB::table('jobs')->where('id', $mappings['jobs'][(string) $job['id']])->update([
                'current_working_revision_id' => $this->mappedNullable($job['current_working_revision_id'], $mappings['job_revisions']),
                'issued_revision_id' => $this->mappedNullable($job['issued_revision_id'], $mappings['job_revisions']),
                'accepted_revision_id' => $this->mappedNullable($job['accepted_revision_id'], $mappings['job_revisions']),
            ]);
        }
    }

    private function mappedNullable(mixed $sourceId, array $mapping): ?int
    {
        return $sourceId === null ? null : $mapping[(string) $sourceId];
    }

    private function mappingChecksum(array $mappings): string
    {
        foreach ($mappings as &$mapping) {
            ksort($mapping, SORT_NUMERIC);
        }
        ksort($mappings);

        return hash('sha256', json_encode($mappings, JSON_THROW_ON_ERROR));
    }

    private function timestamps(array $attributes): array
    {
        return array_merge($attributes, ['created_at' => now(), 'updated_at' => now()]);
    }
}
