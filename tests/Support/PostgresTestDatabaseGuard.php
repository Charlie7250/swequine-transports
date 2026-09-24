<?php

namespace Tests\Support;

use LogicException;

class PostgresTestDatabaseGuard
{
    public static function assertSafe(array $environment): void
    {
        if (($environment['DB_URL'] ?? '') !== '') {
            throw new LogicException('DB_URL must be empty for PostgreSQL integration tests.');
        }

        $isExplicit = ($environment['APP_ENV'] ?? '') === 'testing'
            && ($environment['DB_CONNECTION'] ?? '') === 'pgsql'
            && ($environment['POSTGRES_INTEGRATION_TEST'] ?? '') === 'true';
        $isIsolated = str_ends_with((string) ($environment['DB_DATABASE'] ?? ''), '_test');

        if (! $isExplicit || ! $isIsolated) {
            throw new LogicException('PostgreSQL integration tests require an isolated _test database before bootstrap.');
        }
    }
}
