<?php

namespace App\Services\DataMigration;

use JsonException;
use UnexpectedValueException;

class PostgresBackupManifest
{
    public function create(string $backupPath, array $targetIdentity, string $outputPath, bool $restoreTested): array
    {
        $manifest = [
            'format' => 'custom',
            'backup_path' => (string) realpath($backupPath),
            'backup_checksum' => is_file($backupPath) ? hash_file('sha256', $backupPath) : '',
            'target_identity' => $targetIdentity,
            'restore_tested' => $restoreTested,
        ];
        $this->assertFormat($manifest);
        $this->assertBackup($manifest);

        if (file_put_contents($outputPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, LOCK_EX) === false) {
            throw new UnexpectedValueException('The backup manifest could not be written.');
        }

        chmod($outputPath, 0600);

        return $manifest;
    }

    public function validate(string $manifestPath, array $targetIdentity): array
    {
        $manifest = $this->read($manifestPath);
        $this->assertFormat($manifest);

        if (($manifest['target_identity'] ?? null) !== $targetIdentity) {
            throw new UnexpectedValueException('The backup manifest is not bound to this PostgreSQL target.');
        }

        $this->assertBackup($manifest);

        return $manifest;
    }

    private function read(string $manifestPath): array
    {
        if (! is_file($manifestPath)) {
            throw new UnexpectedValueException('The backup manifest does not exist.');
        }

        try {
            return json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new UnexpectedValueException('The backup manifest is not valid JSON.');
        }
    }

    private function assertFormat(array $manifest): void
    {
        if (($manifest['format'] ?? null) !== 'custom') {
            throw new UnexpectedValueException('A custom-format PostgreSQL backup is required.');
        }

        if (($manifest['restore_tested'] ?? false) !== true) {
            throw new UnexpectedValueException('The PostgreSQL backup must pass a restore test.');
        }
    }

    private function assertBackup(array $manifest): void
    {
        $path = $manifest['backup_path'] ?? '';

        if (! is_file($path) || file_get_contents($path, false, null, 0, 5) !== 'PGDMP') {
            throw new UnexpectedValueException('The backup is not a readable PostgreSQL custom archive.');
        }

        if (! hash_equals((string) ($manifest['backup_checksum'] ?? ''), hash_file('sha256', $path))) {
            throw new UnexpectedValueException('The PostgreSQL backup checksum does not match.');
        }
    }
}
