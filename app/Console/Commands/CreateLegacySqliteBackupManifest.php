<?php

namespace App\Console\Commands;

use App\Services\DataMigration\PostgresBackupManifest;
use App\Services\DataMigration\PostgresTargetInspector;
use Illuminate\Console\Command;
use Throwable;

class CreateLegacySqliteBackupManifest extends Command
{
    protected $signature = 'legacy-sqlite:backup-manifest {--backup= : PostgreSQL custom archive path} {--output= : Manifest output path} {--restore-tested : Confirm a disposable restore passed}';

    protected $description = 'Create a target-bound manifest for a validated PostgreSQL backup';

    public function handle(PostgresTargetInspector $target, PostgresBackupManifest $manifest): int
    {
        if (! $this->option('backup') || ! $this->option('output') || ! $this->option('restore-tested')) {
            $this->error('The --backup, --output, and --restore-tested options are required.');

            return self::FAILURE;
        }

        try {
            $target->assertPostgres();
            $target->assertMigrationsCurrent();
            $created = $manifest->create(
                (string) $this->option('backup'),
                $target->identity(),
                (string) $this->option('output'),
                true,
            );
            $this->line(json_encode($created, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
