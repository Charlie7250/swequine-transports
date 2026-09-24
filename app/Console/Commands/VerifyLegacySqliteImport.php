<?php

namespace App\Console\Commands;

use App\Services\DataMigration\LegacyImportVerifier;
use Illuminate\Console\Command;
use Throwable;

class VerifyLegacySqliteImport extends Command
{
    protected $signature = 'legacy-sqlite:verify {run-id : Committed legacy import run identifier}';

    protected $description = 'Verify a committed legacy SQLite import and explicit seed';

    public function handle(LegacyImportVerifier $verifier): int
    {
        try {
            $result = $verifier->verify((int) $this->argument('run-id'));
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
