<?php

namespace Tests\Integration\Postgres;

use App\Models\Customer;
use App\Models\LegacyImportRun;
use App\Models\User;
use App\Services\DataMigration\LegacyImportReportWriter;
use App\Services\DataMigration\PostgresTargetInspector;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use LogicException;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class LegacyImportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Artisan::call('up');
        parent::tearDown();
    }

    #[Test]
    public function the_explicit_postgres_profile_uses_a_disposable_database(): void
    {
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('sweq_transports_test', config('database.connections.pgsql.database'));
        $this->assertTrue(config('database.postgres_integration_test'));
        $this->assertSame('/tmp/sweq-transports-postgres-tests', storage_path());
        $this->assertNotSame(base_path('storage'), storage_path());
    }

    #[Test]
    public function dry_run_does_not_write_business_records_or_the_import_ledger(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);

        $exitCode = Artisan::call('legacy-sqlite:import', ['--source' => $this->createLegacySource()]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('legacy_import_runs', 0);
    }

    #[Test]
    public function committed_import_seed_and_verification_preserve_relationships_and_restore_availability(): void
    {
        $user = User::factory()->create(['email' => 'ops@sweq.local', 'password' => 'password']);
        Customer::query()->create(['name' => 'Existing PostgreSQL Customer']);
        $source = $this->createLegacySource();
        $manifest = $this->createBackupManifest();

        $exitCode = Artisan::call('legacy-sqlite:import', [
            '--source' => $source,
            '--commit' => true,
            '--backup-manifest' => $manifest,
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $run = LegacyImportRun::query()->sole();
        $this->assertSame('committed_pending_seed', $run->status);
        $this->assertTrue(app()->isDownForMaintenance());
        $this->assertSame($user->id, $run->identifier_mappings['users']['1']);
        $commitReport = json_decode(file_get_contents($run->report_path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(hash_file('sha256', $source), $commitReport['source_checksum']);

        $this->seed(DatabaseSeeder::class);
        $verifyExitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => $run->id]);

        $this->assertSame(0, $verifyExitCode, Artisan::output());
        $this->assertSame('verified', $run->fresh()->status);
        $this->assertFalse(app()->isDownForMaintenance());
        $this->assertDatabaseCount('customers', 3);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Review Customer');

        $repeatExitCode = Artisan::call('legacy-sqlite:import', [
            '--source' => $source,
            '--commit' => true,
            '--backup-manifest' => $manifest,
        ]);

        $this->assertSame(1, $repeatExitCode);
        $failedRun = LegacyImportRun::query()->where('status', 'failed')->sole();
        $this->assertStringContainsString('Resume verification instead.', $failedRun->failure_message);
        $this->assertDatabaseCount('customers', 3);
    }

    #[Test]
    public function an_existing_matching_fuel_row_is_reconciled_without_changing_its_timestamps(): void
    {
        $this->seed(DatabaseSeeder::class);
        $fuel = DB::table('weekly_fuel_prices')->where('source', 'manual_texaco_entry')->sole();
        $originalUpdatedAt = $fuel->updated_at;
        $source = $this->createLegacySource();

        $exitCode = Artisan::call('legacy-sqlite:import', [
            '--source' => $source,
            '--commit' => true,
            '--backup-manifest' => $this->createBackupManifest(),
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $run = LegacyImportRun::query()->sole();
        $this->assertSame($fuel->id, $run->identifier_mappings['weekly_fuel_prices']['1']);
        $this->assertSame($originalUpdatedAt, DB::table('weekly_fuel_prices')->find($fuel->id)->updated_at);

        $this->seed(DatabaseSeeder::class);
        $verifyExitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => $run->id]);

        $this->assertSame(0, $verifyExitCode, Artisan::output());
        $this->assertSame('verified', $run->fresh()->status);
    }

    #[Test]
    public function missing_explicit_seed_keeps_writes_frozen(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        $source = $this->createLegacySource();
        $manifest = $this->createBackupManifest();
        Artisan::call('legacy-sqlite:import', [
            '--source' => $source,
            '--commit' => true,
            '--backup-manifest' => $manifest,
        ]);
        $run = LegacyImportRun::query()->where('status', 'committed_pending_seed')->sole();

        $exitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => $run->id]);

        $this->assertSame(1, $exitCode);
        $this->assertSame('post_seed_failed', $run->fresh()->status);
        $this->assertTrue(app()->isDownForMaintenance());
    }

    #[Test]
    public function pending_migrations_block_import_preflight(): void
    {
        $migration = DB::table('migrations')->orderByDesc('id')->value('migration');
        DB::table('migrations')->where('migration', $migration)->delete();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Pending PostgreSQL migrations: '.$migration);

        app(PostgresTargetInspector::class)->assertMigrationsCurrent();
    }

    #[Test]
    public function report_failure_preserves_committed_mappings_for_verification_retry(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        $this->mock(LegacyImportReportWriter::class, function ($mock): void {
            $mock->shouldReceive('write')->once()->andThrow(new RuntimeException('report unavailable'));
        });

        $exitCode = Artisan::call('legacy-sqlite:import', [
            '--source' => $this->createLegacySource(),
            '--commit' => true,
            '--backup-manifest' => $this->createBackupManifest(),
        ]);

        $this->assertSame(1, $exitCode);
        $run = LegacyImportRun::query()->sole();
        $this->assertSame('committed_pending_seed', $run->status);
        $this->assertNotEmpty($run->identifier_mappings);

        $this->app->forgetInstance(LegacyImportReportWriter::class);
        $this->seed(DatabaseSeeder::class);
        $verifyExitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => $run->id]);

        $this->assertSame(0, $verifyExitCode, Artisan::output());
        $this->assertSame('verified', $run->fresh()->status);
    }

    #[Test]
    public function verification_rejects_a_swapped_imported_customer_relationship(): void
    {
        $run = $this->commitLegacyImport();
        $jobs = $run->identifier_mappings['jobs'];
        $customers = $run->identifier_mappings['customers'];
        DB::table('jobs')->where('id', $jobs['1'])->update(['customer_id' => $customers['2']]);
        $this->seed(DatabaseSeeder::class);

        $exitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => $run->id]);

        $this->assertSame(1, $exitCode);
        $this->assertSame('verification_failed', $run->fresh()->status);
        $this->assertTrue(app()->isDownForMaintenance());
    }

    #[Test]
    public function verification_rejects_a_changed_imported_revision_total(): void
    {
        $run = $this->commitLegacyImport();
        DB::table('job_revisions')->where('id', $run->identifier_mappings['job_revisions']['1'])->update(['final_total' => '999.99']);
        $this->seed(DatabaseSeeder::class);

        $exitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => $run->id]);

        $this->assertSame(1, $exitCode);
        $this->assertSame('verification_failed', $run->fresh()->status);
        $this->assertTrue(app()->isDownForMaintenance());
    }

    #[Test]
    public function verification_rejects_an_issuer_added_to_an_imported_revision(): void
    {
        $run = $this->commitLegacyImport();
        $userId = $run->identifier_mappings['users']['1'];
        DB::table('job_revisions')->where('id', $run->identifier_mappings['job_revisions']['1'])->update(['issued_by_user_id' => $userId]);
        $this->seed(DatabaseSeeder::class);

        $exitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => $run->id]);

        $this->assertSame(1, $exitCode);
        $this->assertSame('verification_failed', $run->fresh()->status);
        $this->assertTrue(app()->isDownForMaintenance());
    }

    #[Test]
    public function verification_rejects_a_transport_day_added_to_an_imported_job(): void
    {
        $run = $this->commitLegacyImport();
        $transportDayId = DB::table('transport_days')->insertGetId([
            'run_date' => '2026-08-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('jobs')->where('id', $run->identifier_mappings['jobs']['1'])->update([
            'transport_day_id' => $transportDayId,
            'transport_day_sequence' => 1,
        ]);
        $this->seed(DatabaseSeeder::class);

        $exitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => $run->id]);

        $this->assertSame(1, $exitCode);
        $this->assertSame('verification_failed', $run->fresh()->status);
        $this->assertTrue(app()->isDownForMaintenance());
    }

    #[Test]
    public function a_second_connection_cannot_acquire_the_import_lock(): void
    {
        $inspector = app(PostgresTargetInspector::class);
        $inspector->acquireLock();
        $pdo = new PDO('pgsql:host=postgres;port=5432;dbname=sweq_transports_test', 'sweq', 'sweq');

        try {
            $acquired = $pdo->query('SELECT pg_try_advisory_lock(813776937154462)')->fetchColumn();
            $this->assertFalse($acquired);
        } finally {
            $inspector->releaseLock();
        }
    }

    #[Test]
    public function commit_aborts_when_the_advisory_lock_is_lost_before_business_writes(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        $hasReleasedLock = false;
        DB::listen(function ($query) use (&$hasReleasedLock): void {
            if (! $hasReleasedLock && str_contains($query->sql, 'insert into "legacy_import_runs"')) {
                $hasReleasedLock = true;
                DB::scalar('SELECT pg_advisory_unlock(813776937154462)');
            }
        });

        $exitCode = Artisan::call('legacy-sqlite:import', [
            '--source' => $this->createLegacySource(),
            '--commit' => true,
            '--backup-manifest' => $this->createBackupManifest(),
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertDatabaseCount('customers', 0);
        $this->assertSame('failed', LegacyImportRun::query()->sole()->status);
        $this->assertFalse(app()->isDownForMaintenance());
    }

    #[Test]
    public function schema_fingerprint_changes_when_a_column_default_changes_without_a_migration_record(): void
    {
        $inspector = app(PostgresTargetInspector::class);
        $before = $inspector->schemaFingerprint();

        DB::statement("ALTER TABLE jobs ALTER COLUMN status SET DEFAULT 'quoted'");

        $this->assertNotSame($before, $inspector->schemaFingerprint());
    }

    #[Test]
    public function commit_aborts_when_the_schema_changes_after_preflight(): void
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        $rateChecks = 0;
        $hasChangedSchema = false;
        DB::listen(function ($query) use (&$rateChecks, &$hasChangedSchema): void {
            if (str_contains($query->sql, 'rate_settings') && str_contains($query->sql, 'name')) {
                $rateChecks++;
            }
            if ($rateChecks === 2 && ! $hasChangedSchema) {
                $hasChangedSchema = true;
                DB::statement("ALTER TABLE jobs ALTER COLUMN status SET DEFAULT 'quoted'");
            }
        });

        $exitCode = Artisan::call('legacy-sqlite:import', [
            '--source' => $this->createLegacySource(),
            '--commit' => true,
            '--backup-manifest' => $this->createBackupManifest(),
        ]);

        $this->assertTrue($hasChangedSchema);
        $this->assertSame(1, $exitCode);
        $this->assertDatabaseCount('customers', 0);
        $this->assertFalse(app()->isDownForMaintenance());
    }

    private function createLegacySource(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sweq-postgres-source-');
        $pdo = new PDO('sqlite:'.$path);
        $pdo->exec(file_get_contents(base_path('tests/Fixtures/legacy-import.sql')));

        return $path;
    }

    private function commitLegacyImport(): LegacyImportRun
    {
        User::factory()->create(['email' => 'ops@sweq.local']);
        Artisan::call('legacy-sqlite:import', [
            '--source' => $this->createLegacySource(),
            '--commit' => true,
            '--backup-manifest' => $this->createBackupManifest(),
        ]);

        return LegacyImportRun::query()->where('status', 'committed_pending_seed')->sole();
    }

    private function createBackupManifest(): string
    {
        $backupPath = tempnam(sys_get_temp_dir(), 'sweq-postgres-backup-');
        file_put_contents($backupPath, 'PGDMP'.random_bytes(32));
        $manifestPath = tempnam(sys_get_temp_dir(), 'sweq-postgres-manifest-');
        file_put_contents($manifestPath, json_encode([
            'format' => 'custom',
            'backup_path' => $backupPath,
            'backup_checksum' => hash_file('sha256', $backupPath),
            'target_identity' => app(PostgresTargetInspector::class)->identity(),
            'restore_tested' => true,
        ], JSON_THROW_ON_ERROR));

        return $manifestPath;
    }
}
