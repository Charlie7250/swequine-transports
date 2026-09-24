<?php

namespace Tests\Unit\Support;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\PostgresTestDatabaseGuard;

class PostgresTestDatabaseGuardTest extends TestCase
{
    #[Test]
    public function an_operational_database_is_rejected_before_the_test_application_boots(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PostgreSQL integration tests require an isolated _test database before bootstrap.');

        PostgresTestDatabaseGuard::assertSafe([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_DATABASE' => 'sweq_transports',
            'DB_URL' => '',
            'POSTGRES_INTEGRATION_TEST' => 'true',
        ]);
    }

    #[Test]
    public function a_database_url_is_rejected_before_the_test_application_boots(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('DB_URL must be empty for PostgreSQL integration tests.');

        PostgresTestDatabaseGuard::assertSafe([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_DATABASE' => 'sweq_transports_test',
            'DB_URL' => 'postgresql://sweq:sweq@postgres/sweq_transports',
            'POSTGRES_INTEGRATION_TEST' => 'true',
        ]);
    }

    #[Test]
    public function the_explicit_isolated_profile_is_accepted_before_bootstrap(): void
    {
        PostgresTestDatabaseGuard::assertSafe([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'pgsql',
            'DB_DATABASE' => 'sweq_transports_test',
            'DB_URL' => '',
            'POSTGRES_INTEGRATION_TEST' => 'true',
        ]);

        $this->addToAssertionCount(1);
    }
}
