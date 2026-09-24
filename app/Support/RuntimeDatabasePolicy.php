<?php

namespace App\Support;

use LogicException;

class RuntimeDatabasePolicy
{
    public static function assertSupported(
        string $environment,
        string $driver,
        string $database,
        bool $isPostgresIntegrationTest,
    ): void {
        if ($environment !== 'testing' && $driver !== 'pgsql') {
            throw new LogicException('PostgreSQL is required outside automated tests.');
        }

        if ($environment === 'testing' && $driver === 'sqlite' && $database !== ':memory:') {
            throw new LogicException('Automated tests may use only in-memory SQLite.');
        }

        if ($environment === 'testing' && $driver === 'pgsql' && ! $isPostgresIntegrationTest) {
            throw new LogicException('PostgreSQL tests require the explicit integration profile.');
        }

        if ($environment === 'testing' && $driver === 'pgsql' && ! str_ends_with($database, '_test')) {
            throw new LogicException('PostgreSQL integration tests require a database ending in _test.');
        }

        if ($environment === 'testing' && ! in_array($driver, ['sqlite', 'pgsql'], true)) {
            throw new LogicException('Automated tests require in-memory SQLite or the PostgreSQL integration profile.');
        }
    }
}
