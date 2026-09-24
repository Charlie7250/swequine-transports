<?php

namespace Tests\Unit\Support;

use App\Support\RuntimeDatabasePolicy;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RuntimeDatabasePolicyTest extends TestCase
{
    #[Test]
    public function the_runtime_database_policy_is_available(): void
    {
        $this->assertTrue(class_exists(RuntimeDatabasePolicy::class));
    }

    #[Test]
    public function non_test_sqlite_configuration_is_rejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PostgreSQL is required outside automated tests.');

        RuntimeDatabasePolicy::assertSupported('local', 'sqlite', 'database/database.sqlite', false);
    }

    #[Test]
    public function in_memory_sqlite_is_available_for_standard_tests(): void
    {
        RuntimeDatabasePolicy::assertSupported('testing', 'sqlite', ':memory:', false);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function file_backed_sqlite_is_rejected_during_tests(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Automated tests may use only in-memory SQLite.');

        RuntimeDatabasePolicy::assertSupported('testing', 'sqlite', 'database/database.sqlite', false);
    }

    #[Test]
    public function postgres_testing_requires_the_integration_profile(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PostgreSQL tests require the explicit integration profile.');

        RuntimeDatabasePolicy::assertSupported('testing', 'pgsql', 'sweq_transports_test', false);
    }

    #[Test]
    public function the_postgres_integration_profile_is_accepted(): void
    {
        RuntimeDatabasePolicy::assertSupported('testing', 'pgsql', 'sweq_transports_test', true);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function the_postgres_integration_profile_rejects_an_operational_database_name(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PostgreSQL integration tests require a database ending in _test.');

        RuntimeDatabasePolicy::assertSupported('testing', 'pgsql', 'sweq_transports', true);
    }

    #[Test]
    public function postgres_is_accepted_outside_tests(): void
    {
        RuntimeDatabasePolicy::assertSupported('production', 'pgsql', 'sweq_transports', false);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function other_database_drivers_are_rejected_during_tests(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Automated tests require in-memory SQLite or the PostgreSQL integration profile.');

        RuntimeDatabasePolicy::assertSupported('testing', 'mysql', 'sweq_transports_test', false);
    }
}
