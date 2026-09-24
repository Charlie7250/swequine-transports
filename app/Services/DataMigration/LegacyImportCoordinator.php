<?php

namespace App\Services\DataMigration;

use App\Models\LegacyImportRun;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class LegacyImportCoordinator
{
    public function __construct(
        private readonly PostgresTargetInspector $target,
        private readonly LegacySqliteImportPlanner $planner,
        private readonly LegacySqliteImporter $importer,
        private readonly LegacySqliteSnapshot $snapshot,
        private readonly PostgresBackupManifest $backupManifest,
        private readonly LegacyImportReportWriter $reportWriter,
    ) {}

    public function dryRun(string $sourcePath): array
    {
        $this->target->assertPostgres();
        $this->target->assertMigrationsCurrent();
        $plan = $this->planner->plan($sourcePath);
        $identity = $this->target->identity();
        $this->assertNotCommitted($plan['source_checksum'], $identity);
        $this->importer->preflight($plan);

        return $this->summary('dry-run', $plan, $identity);
    }

    public function commit(string $sourcePath, string $manifestPath): array
    {
        $this->target->assertPostgres();
        $this->target->assertMigrationsCurrent();
        $this->target->acquireLock();

        return $this->commitWhileLocked($sourcePath, $manifestPath);
    }

    private function commitWhileLocked(string $sourcePath, string $manifestPath): array
    {
        $wasDown = app()->isDownForMaintenance();
        $enteredMaintenance = false;
        $businessCommitted = false;
        $run = null;

        try {
            $identity = $this->target->identity();
            $backup = $this->backupManifest->validate($manifestPath, $identity);
            $sourceChecksum = $this->sourceChecksum($sourcePath);
            $this->enterMaintenance($wasDown);
            $enteredMaintenance = ! $wasDown;
            $snapshot = $this->snapshot->create($sourcePath, $this->privateDirectory());
            $plan = $this->planner->plan($snapshot['path']);
            $run = $this->startRun($sourcePath, $sourceChecksum, $snapshot, $plan, $identity, $manifestPath, $backup, $wasDown);
            $this->recheck($sourcePath, $sourceChecksum, $identity, $plan);
            $result = $this->importer->import(
                $plan,
                beforeWrite: fn () => $this->assertWriteBoundary($identity),
                afterWrite: fn (array $result) => $this->markCommitted($run, $result),
            );
            $businessCommitted = true;
            $summary = $this->completeRun($run, $plan, $result);

            return $summary;
        } catch (Throwable $exception) {
            $this->recordFailure($run, $exception, $businessCommitted);

            throw $exception;
        } finally {
            if ($enteredMaintenance && ! $businessCommitted) {
                Artisan::call('up');
            }
            $this->target->releaseLock();
        }
    }

    private function recheck(string $sourcePath, string $checksum, array $identity, array $plan): void
    {
        if (! hash_equals($checksum, $this->sourceChecksum($sourcePath))) {
            throw new RuntimeException('The legacy SQLite source changed after preflight.');
        }

        $this->target->assertMigrationsCurrent();
        if ($this->target->identity() !== $identity) {
            throw new RuntimeException('The PostgreSQL target identity changed after preflight.');
        }
        $this->assertNotCommitted($checksum, $identity);
        $this->importer->preflight($plan);
    }

    private function startRun(
        string $sourcePath,
        string $sourceChecksum,
        array $snapshot,
        array $plan,
        array $identity,
        string $manifestPath,
        array $backup,
        bool $wasDown,
    ): LegacyImportRun {
        return LegacyImportRun::query()->create([
            'source_path' => (string) realpath($sourcePath),
            'source_checksum' => $sourceChecksum,
            'source_schema_fingerprint' => $plan['source_schema_fingerprint'],
            'target_identity' => $identity,
            'target_schema_fingerprint' => $identity['schema_fingerprint'],
            'importer_version' => '1',
            'defaults_version' => '1',
            'phase' => 'import',
            'status' => 'importing',
            'is_dry_run' => false,
            'was_maintenance_active' => $wasDown,
            'source_snapshot_path' => $snapshot['path'],
            'source_snapshot_checksum' => $snapshot['checksum'],
            'backup_manifest_path' => (string) realpath($manifestPath),
            'backup_checksum' => $backup['backup_checksum'],
            'counts' => $plan['counts'],
            'started_at' => now(),
        ]);
    }

    private function completeRun(LegacyImportRun $run, array $plan, array $result): array
    {
        $summary = array_merge($this->summary('committed', $plan, $run->target_identity), [
            'run_id' => $run->id,
            'source_checksum' => $run->source_checksum,
            'source_snapshot_checksum' => $run->source_snapshot_checksum,
            'mapping_checksum' => $result['mapping_checksum'],
            'next_step' => 'Run DatabaseSeeder explicitly, then run legacy-sqlite:verify '.$run->id.'.',
        ]);
        $reportPath = $this->reportWriter->write($this->privateDirectory(), 'run-'.$run->id.'-commit', array_merge($summary, [
            'identifier_mappings' => $result['mappings'],
        ]));
        $run->update(['report_path' => $reportPath]);

        return $summary;
    }

    private function markCommitted(LegacyImportRun $run, array $result): void
    {
        $run->update([
            'phase' => 'post_seed',
            'status' => 'committed_pending_seed',
            'identifier_mappings' => $result['mappings'],
            'mapping_checksum' => $result['mapping_checksum'],
            'committed_at' => now(),
        ]);
    }

    private function recordFailure(?LegacyImportRun $run, Throwable $exception, bool $businessCommitted): void
    {
        if ($run === null) {
            return;
        }

        $attributes = ['failure_message' => $exception->getMessage()];
        if ($businessCommitted) {
            $attributes['phase'] = 'post_commit_report';
        } else {
            $attributes['phase'] = 'import';
            $attributes['status'] = 'failed';
        }
        $run->update($attributes);
    }

    private function assertNotCommitted(string $sourceChecksum, array $identity): void
    {
        $existing = LegacyImportRun::query()->where('source_checksum', $sourceChecksum)->whereIn('status', [
            'committed_pending_seed',
            'post_seed_failed',
            'verification_failed',
            'verified',
            'post_commit_failed',
        ])->get()->first(fn (LegacyImportRun $run): bool => $run->target_identity === $identity);

        if ($existing !== null) {
            throw new RuntimeException("Legacy source already has committed import run {$existing->id}. Resume verification instead.");
        }
    }

    private function enterMaintenance(bool $wasDown): void
    {
        if (! $wasDown && Artisan::call('down') !== 0) {
            throw new RuntimeException('The application could not enter maintenance mode.');
        }
    }

    private function sourceChecksum(string $sourcePath): string
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new RuntimeException('The legacy SQLite source is not readable.');
        }

        return hash_file('sha256', $sourcePath);
    }

    private function assertWriteBoundary(array $identity): void
    {
        if ($this->target->identity() !== $identity) {
            throw new RuntimeException('The PostgreSQL target schema changed before import writes began.');
        }

        $this->target->assertLockHeld();
    }

    private function privateDirectory(): string
    {
        return storage_path('app/private/legacy-imports');
    }

    private function summary(string $mode, array $plan, array $identity): array
    {
        return [
            'mode' => $mode,
            'source_checksum' => $plan['source_checksum'],
            'source_schema_fingerprint' => $plan['source_schema_fingerprint'],
            'target_identity' => $identity,
            'counts' => $plan['counts'],
        ];
    }
}
