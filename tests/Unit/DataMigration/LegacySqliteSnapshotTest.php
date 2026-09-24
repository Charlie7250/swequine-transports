<?php

namespace Tests\Unit\DataMigration;

use App\Services\DataMigration\LegacySqliteSnapshot;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LegacySqliteSnapshotTest extends TestCase
{
    #[Test]
    public function the_snapshot_service_is_available(): void
    {
        $this->assertTrue(class_exists(LegacySqliteSnapshot::class));
    }

    #[Test]
    public function snapshot_creation_preserves_the_source_and_records_its_checksum(): void
    {
        $source = $this->createLegacySource();
        $before = hash_file('sha256', $source);
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sweq-snapshot-'.bin2hex(random_bytes(4));

        $snapshot = (new LegacySqliteSnapshot)->create($source, $directory);

        $this->assertSame($before, hash_file('sha256', $source));
        $this->assertFileExists($snapshot['path']);
        $this->assertSame(hash_file('sha256', $snapshot['path']), $snapshot['checksum']);
        $this->assertSame('ok', $snapshot['integrity_check']);
    }

    private function createLegacySource(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sweq-snapshot-source-');
        $pdo = new PDO('sqlite:'.$path);
        $pdo->exec(file_get_contents(dirname(__DIR__, 2).'/Fixtures/legacy-import.sql'));

        return $path;
    }
}
