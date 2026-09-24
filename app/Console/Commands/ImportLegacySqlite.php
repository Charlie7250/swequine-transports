<?php

namespace App\Console\Commands;

use App\Services\DataMigration\LegacyImportCoordinator;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacySqlite extends Command
{
    protected $signature = 'legacy-sqlite:import {--source= : Legacy SQLite database path} {--commit : Write the approved import} {--backup-manifest= : Validated PostgreSQL backup manifest path}';

    protected $description = 'Plan or commit the legacy SQLite import into PostgreSQL';

    public function handle(LegacyImportCoordinator $coordinator): int
    {
        $source = (string) $this->option('source');

        if ($source === '') {
            $this->error('The --source option is required.');

            return self::FAILURE;
        }

        if ($this->option('commit') && ! $this->option('backup-manifest')) {
            $this->error('The --backup-manifest option is required with --commit.');

            return self::FAILURE;
        }

        try {
            $result = $this->option('commit')
                ? $coordinator->commit($source, (string) $this->option('backup-manifest'))
                : $coordinator->dryRun($source);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
