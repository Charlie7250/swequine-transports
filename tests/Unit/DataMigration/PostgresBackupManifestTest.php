<?php

namespace Tests\Unit\DataMigration;

use App\Services\DataMigration\PostgresBackupManifest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class PostgresBackupManifestTest extends TestCase
{
    #[Test]
    public function the_backup_manifest_validator_is_available(): void
    {
        $this->assertTrue(class_exists(PostgresBackupManifest::class));
    }

    #[Test]
    public function a_valid_custom_backup_is_bound_to_the_exact_target(): void
    {
        [$manifestPath, $identity, $checksum] = $this->createManifest();

        $manifest = (new PostgresBackupManifest)->validate($manifestPath, $identity);

        $this->assertSame($checksum, $manifest['backup_checksum']);
    }

    #[Test]
    public function a_manifest_for_another_target_is_rejected(): void
    {
        [$manifestPath, $identity] = $this->createManifest();
        $identity['database'] = 'another_database';

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The backup manifest is not bound to this PostgreSQL target.');

        (new PostgresBackupManifest)->validate($manifestPath, $identity);
    }

    #[Test]
    public function a_target_bound_manifest_is_created_only_for_a_restore_tested_archive(): void
    {
        [$existingManifest, $identity] = $this->createManifest();
        $existing = json_decode(file_get_contents($existingManifest), true, 512, JSON_THROW_ON_ERROR);
        $output = tempnam(sys_get_temp_dir(), 'sweq-created-manifest-');

        $manifest = (new PostgresBackupManifest)->create($existing['backup_path'], $identity, $output, true);

        $this->assertSame($identity, $manifest['target_identity']);
        $this->assertSame($manifest, (new PostgresBackupManifest)->validate($output, $identity));
    }

    private function createManifest(): array
    {
        $backupPath = tempnam(sys_get_temp_dir(), 'sweq-backup-');
        file_put_contents($backupPath, 'PGDMP'.random_bytes(32));
        $checksum = hash_file('sha256', $backupPath);
        $identity = [
            'system_identifier' => '7623973423',
            'server_address' => '172.18.0.2',
            'port' => 5432,
            'database' => 'sweq_transports',
            'schema' => 'public',
            'schema_fingerprint' => str_repeat('a', 64),
        ];
        $manifestPath = tempnam(sys_get_temp_dir(), 'sweq-manifest-');
        file_put_contents($manifestPath, json_encode([
            'format' => 'custom',
            'backup_path' => $backupPath,
            'backup_checksum' => $checksum,
            'target_identity' => $identity,
            'restore_tested' => true,
        ], JSON_THROW_ON_ERROR));

        return [$manifestPath, $identity, $checksum];
    }
}
