<?php

namespace App\Services\DataMigration;

use PDO;
use RuntimeException;

class LegacySqliteSnapshot
{
    public function create(string $sourcePath, string $directory): array
    {
        $this->prepareDirectory($directory);
        $snapshotPath = $directory.DIRECTORY_SEPARATOR.'legacy-'.gmdate('YmdHis').'-'.bin2hex(random_bytes(4)).'.sqlite';
        $source = new PDO('sqlite:'.$sourcePath);
        $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $source->exec('VACUUM INTO '.$source->quote($snapshotPath));
        $integrity = $this->integrityCheck($snapshotPath);

        if ($integrity !== 'ok') {
            throw new RuntimeException('The SQLite snapshot failed its integrity check.');
        }

        chmod($snapshotPath, 0400);

        return ['path' => $snapshotPath, 'checksum' => hash_file('sha256', $snapshotPath), 'integrity_check' => $integrity];
    }

    private function prepareDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('The private import directory could not be created.');
        }

        chmod($directory, 0700);
    }

    private function integrityCheck(string $snapshotPath): string
    {
        $snapshot = new PDO('sqlite:'.$snapshotPath);

        return (string) $snapshot->query('PRAGMA integrity_check')->fetchColumn();
    }
}
