<?php

namespace App\Services\DataMigration;

use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

class PostgresTargetInspector
{
    private const LOCK_ID = 813776937154462;

    private ?int $lockedBackendId = null;

    public function assertPostgres(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            throw new LogicException('Legacy imports require a PostgreSQL target.');
        }
    }

    public function assertMigrationsCurrent(): void
    {
        $applied = DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
        $pending = array_values(array_diff($this->migrationFileNames(), $applied));

        if ($pending !== []) {
            throw new LogicException('Pending PostgreSQL migrations: '.implode(', ', $pending));
        }
    }

    public function identity(): array
    {
        $this->assertPostgres();
        $server = DB::selectOne('SELECT inet_server_addr()::text AS address, inet_server_port() AS port, current_database() AS database, current_schema() AS schema');
        $control = DB::selectOne('SELECT system_identifier::text AS system_identifier FROM pg_control_system()');

        return [
            'system_identifier' => $control->system_identifier,
            'server_address' => $server->address,
            'port' => (int) $server->port,
            'database' => $server->database,
            'schema' => $server->schema,
            'schema_fingerprint' => $this->schemaFingerprint(),
        ];
    }

    public function schemaFingerprint(): string
    {
        $definition = [
            'columns' => $this->columns(),
            'constraints' => $this->constraints(),
            'indexes' => $this->indexes(),
        ];

        return hash('sha256', json_encode($definition, JSON_THROW_ON_ERROR));
    }

    public function acquireLock(): void
    {
        $result = DB::selectOne('SELECT pg_backend_pid() AS backend_id, pg_try_advisory_lock(?) AS acquired', [self::LOCK_ID]);

        if (! $result->acquired) {
            throw new RuntimeException('Another legacy import holds the PostgreSQL advisory lock.');
        }

        $this->lockedBackendId = (int) $result->backend_id;
    }

    public function assertLockHeld(): void
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('The legacy import lock must be checked inside the business transaction.');
        }

        $backendId = (int) DB::scalar('SELECT pg_backend_pid()');
        $isHeld = DB::scalar(
            "SELECT EXISTS (SELECT 1 FROM pg_locks WHERE locktype = 'advisory' AND pid = pg_backend_pid() AND classid::bigint = CAST(? AS bigint) AND objid::bigint = CAST(? AS bigint) AND objsubid = 1 AND granted)",
            [self::LOCK_ID >> 32, self::LOCK_ID & 0xFFFFFFFF],
        );

        if ($this->lockedBackendId !== $backendId || ! $isHeld) {
            throw new RuntimeException('The PostgreSQL advisory lock was lost before import writes began.');
        }
    }

    public function releaseLock(): void
    {
        DB::scalar('SELECT pg_advisory_unlock(?)', [self::LOCK_ID]);
        $this->lockedBackendId = null;
    }

    private function migrationFileNames(): array
    {
        $paths = glob(database_path('migrations/*.php')) ?: [];
        $names = array_map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME), $paths);
        sort($names);

        return $names;
    }

    private function columns(): array
    {
        return DB::select(<<<'SQL'
            SELECT n.nspname AS schema_name, c.relname AS table_name, a.attnum AS ordinal_position,
                a.attname AS column_name, pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type,
                a.attnotnull AS is_not_null, pg_get_expr(d.adbin, d.adrelid) AS column_default,
                a.attidentity AS identity_kind, a.attgenerated AS generated_kind, col.collname AS collation
            FROM pg_attribute a
            JOIN pg_class c ON c.oid = a.attrelid
            JOIN pg_namespace n ON n.oid = c.relnamespace
            LEFT JOIN pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum
            LEFT JOIN pg_collation col ON col.oid = a.attcollation
            WHERE n.nspname = current_schema() AND c.relkind IN ('r', 'p')
                AND a.attnum > 0 AND NOT a.attisdropped
            ORDER BY n.nspname, c.relname, a.attnum
            SQL);
    }

    private function constraints(): array
    {
        return DB::select(<<<'SQL'
            SELECT n.nspname AS schema_name, c.relname AS table_name, con.conname AS constraint_name,
                con.contype AS constraint_type, con.condeferrable AS is_deferrable,
                con.condeferred AS is_deferred, con.convalidated AS is_validated,
                pg_get_constraintdef(con.oid, true) AS definition
            FROM pg_constraint con
            JOIN pg_class c ON c.oid = con.conrelid
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = current_schema()
            ORDER BY n.nspname, c.relname, con.conname
            SQL);
    }

    private function indexes(): array
    {
        return DB::select(<<<'SQL'
            SELECT schemaname AS schema_name, tablename AS table_name, indexname AS index_name, indexdef AS definition
            FROM pg_indexes
            WHERE schemaname = current_schema()
            ORDER BY schemaname, tablename, indexname
            SQL);
    }
}
