<?php

namespace App\Services\DataMigration;

use App\Models\LegacyImportRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class LegacyImportVerifier
{
    public function __construct(
        private readonly PostgresTargetInspector $target,
        private readonly LegacyImportReportWriter $reportWriter,
        private readonly LegacyImportReconciler $reconciler,
    ) {}

    public function verify(int $runId): array
    {
        $this->target->assertPostgres();
        $this->target->assertMigrationsCurrent();
        $this->target->acquireLock();

        try {
            return $this->verifyWhileLocked($runId);
        } finally {
            $this->target->releaseLock();
        }
    }

    private function verifyWhileLocked(int $runId): array
    {
        $run = LegacyImportRun::query()->findOrFail($runId);
        $this->assertVerifiable($run);
        $this->freezeWrites();

        try {
            $results = $this->runChecks($run);
            $reportPath = $this->writeReport($run, $results);
            $run->update([
                'phase' => 'verified',
                'status' => 'verified',
                'verification_results' => $results,
                'report_path' => $reportPath,
                'failure_message' => null,
                'verified_at' => now(),
            ]);
            $this->restoreAvailability($run);

            return ['run_id' => $run->id, 'status' => 'verified', 'verification_results' => $results];
        } catch (Throwable $exception) {
            $this->recordFailure($run, $exception);

            throw $exception;
        }
    }

    private function runChecks(LegacyImportRun $run): array
    {
        $this->assertTarget($run);
        $this->assertEvidenceFiles($run);
        $this->assertMappingChecksum($run);
        $this->assertMappedRows($run);
        $this->reconciler->verify($run);
        $this->assertExplicitSeed();

        return [
            'target_identity' => true,
            'evidence_files' => true,
            'mapping_checksum' => true,
            'mapped_rows' => true,
            'relationships' => true,
            'explicit_seed' => true,
        ];
    }

    private function assertTarget(LegacyImportRun $run): void
    {
        if ($this->target->identity() !== $run->target_identity) {
            throw new RuntimeException('The PostgreSQL target no longer matches the committed import.');
        }
    }

    private function assertEvidenceFiles(LegacyImportRun $run): void
    {
        $this->assertChecksum($run->source_path, $run->source_checksum, 'legacy SQLite source');
        $this->assertChecksum($run->source_snapshot_path, $run->source_snapshot_checksum, 'SQLite snapshot');
        $manifest = json_decode((string) file_get_contents($run->backup_manifest_path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertChecksum($manifest['backup_path'] ?? '', $run->backup_checksum, 'PostgreSQL backup');
    }

    private function assertChecksum(?string $path, ?string $checksum, string $label): void
    {
        if ($path === null || $checksum === null || ! is_file($path) || ! hash_equals($checksum, hash_file('sha256', $path))) {
            throw new RuntimeException("The {$label} checksum no longer matches.");
        }
    }

    private function assertMappingChecksum(LegacyImportRun $run): void
    {
        $mappings = $run->identifier_mappings ?? [];
        foreach ($mappings as &$mapping) {
            ksort($mapping, SORT_NUMERIC);
        }
        ksort($mappings);
        $checksum = hash('sha256', json_encode($mappings, JSON_THROW_ON_ERROR));

        if (! hash_equals((string) $run->mapping_checksum, $checksum)) {
            throw new RuntimeException('The identifier mapping checksum no longer matches.');
        }
    }

    private function assertMappedRows(LegacyImportRun $run): void
    {
        foreach ($run->identifier_mappings ?? [] as $table => $mapping) {
            $ids = array_values($mapping);
            if (DB::table($table)->whereIn('id', $ids)->count() !== count($ids)) {
                throw new RuntimeException("Mapped PostgreSQL rows are missing from {$table}.");
            }
        }
    }

    private function assertExplicitSeed(): void
    {
        $rate = DB::table('rate_settings')->where('name', 'Initial internal rates')->where('is_active', true)->first();
        if ($rate === null || number_format((float) $rate->one_horse_multiplier, 6, '.', '') !== '1.500000' || number_format((float) $rate->two_horse_multiplier, 6, '.', '') !== '1.750000') {
            throw new PostSeedVerificationException('DatabaseSeeder has not created the approved active rate configuration.');
        }

        $fuel = DB::table('weekly_fuel_prices')->where('week_commencing', '2026-08-10')->where('source', 'manual_texaco_entry')->get();
        if ($fuel->count() !== 1 || number_format((float) $fuel->first()->price_per_litre_inc_vat, 4, '.', '') !== '1.5300' || ! (bool) $fuel->first()->is_active) {
            throw new PostSeedVerificationException('The approved weekly fuel row is not unique and active.');
        }
    }

    private function assertVerifiable(LegacyImportRun $run): void
    {
        if (! in_array($run->status, ['committed_pending_seed', 'post_seed_failed', 'verification_failed'], true)) {
            throw new RuntimeException("Legacy import run {$run->id} cannot be verified from status {$run->status}.");
        }
    }

    private function freezeWrites(): void
    {
        if (! app()->isDownForMaintenance() && Artisan::call('down') !== 0) {
            throw new RuntimeException('The application could not enter maintenance mode for verification.');
        }
    }

    private function restoreAvailability(LegacyImportRun $run): void
    {
        if (! $run->was_maintenance_active && Artisan::call('up') !== 0) {
            throw new RuntimeException('Verification passed, but application availability could not be restored.');
        }
    }

    private function recordFailure(LegacyImportRun $run, Throwable $exception): void
    {
        $run->update([
            'phase' => 'verification',
            'status' => $exception instanceof PostSeedVerificationException ? 'post_seed_failed' : 'verification_failed',
            'failure_message' => $exception->getMessage(),
        ]);
    }

    private function writeReport(LegacyImportRun $run, array $results): string
    {
        return $this->reportWriter->write(
            storage_path('app/private/legacy-imports'),
            'run-'.$run->id.'-verification',
            ['run_id' => $run->id, 'status' => 'verified', 'verification_results' => $results],
        );
    }
}
