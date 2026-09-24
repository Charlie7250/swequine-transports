<?php

namespace Tests\Feature\DataMigration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacySqliteCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function legacy_import_and_verification_commands_are_registered(): void
    {
        $commands = Artisan::all();

        $this->assertArrayHasKey('legacy-sqlite:import', $commands);
        $this->assertArrayHasKey('legacy-sqlite:verify', $commands);
        $this->assertArrayHasKey('legacy-sqlite:backup-manifest', $commands);
    }

    #[Test]
    public function committing_an_import_requires_a_backup_manifest(): void
    {
        $exitCode = Artisan::call('legacy-sqlite:import', [
            '--source' => database_path('database.sqlite'),
            '--commit' => true,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('The --backup-manifest option is required with --commit.', Artisan::output());
    }

    #[Test]
    public function dry_run_rejects_a_non_postgres_target_without_writing_a_ledger_row(): void
    {
        $exitCode = Artisan::call('legacy-sqlite:import', [
            '--source' => database_path('database.sqlite'),
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Legacy imports require a PostgreSQL target.', Artisan::output());
        $this->assertDatabaseCount('legacy_import_runs', 0);
    }

    #[Test]
    public function verification_rejects_a_non_postgres_target(): void
    {
        $exitCode = Artisan::call('legacy-sqlite:verify', ['run-id' => 1]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Legacy imports require a PostgreSQL target.', Artisan::output());
    }
}
