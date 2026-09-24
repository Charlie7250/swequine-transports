<?php

namespace Tests\Unit\Support;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ComposeRuntimeSafetyTest extends TestCase
{
    #[Test]
    public function compose_uses_database_backed_maintenance_state(): void
    {
        $compose = file_get_contents(dirname(__DIR__, 3).'/compose.yaml');

        $this->assertStringContainsString('APP_MAINTENANCE_DRIVER: cache', $compose);
        $this->assertStringContainsString('APP_MAINTENANCE_STORE: database', $compose);
    }

    #[Test]
    public function docker_build_context_excludes_private_migration_evidence(): void
    {
        $dockerIgnore = file_get_contents(dirname(__DIR__, 3).'/.dockerignore');

        $this->assertStringContainsString('database/database.sqlite', $dockerIgnore);
        $this->assertStringContainsString('storage/app/private/legacy-imports', $dockerIgnore);
    }
}
