<?php

namespace Tests\Feature\DataMigration;

use App\Services\DataMigration\PostgresTargetInspector;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostgresTargetInspectorTest extends TestCase
{
    #[Test]
    public function the_postgres_target_inspector_is_available(): void
    {
        $this->assertTrue(class_exists(PostgresTargetInspector::class));
    }

    #[Test]
    public function a_non_postgres_import_target_is_rejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Legacy imports require a PostgreSQL target.');

        (new PostgresTargetInspector)->assertPostgres();
    }
}
