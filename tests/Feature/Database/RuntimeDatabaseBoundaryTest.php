<?php

namespace Tests\Feature\Database;

use App\Providers\AppServiceProvider;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RuntimeDatabaseBoundaryTest extends TestCase
{
    #[Test]
    public function application_boot_rejects_file_backed_sqlite(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', database_path('database.sqlite'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Automated tests may use only in-memory SQLite.');

        (new AppServiceProvider($this->app))->boot();
    }

    #[Test]
    public function application_boot_rejects_an_operational_database_from_db_url(): void
    {
        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql.database', 'sweq_transports_test');
        config()->set('database.connections.pgsql.url', 'postgresql://sweq:sweq@postgres:5432/sweq_transports');
        config()->set('database.postgres_integration_test', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PostgreSQL integration tests require a database ending in _test.');

        (new AppServiceProvider($this->app))->boot();
    }

    #[Test]
    public function application_boot_rejects_a_conflicting_sqlite_db_url(): void
    {
        config()->set('database.default', 'pgsql');
        config()->set('database.connections.pgsql.database', 'sweq_transports_test');
        config()->set('database.connections.pgsql.url', 'sqlite:///database/database.sqlite');
        config()->set('database.postgres_integration_test', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Automated tests may use only in-memory SQLite.');

        (new AppServiceProvider($this->app))->boot();
    }
}
