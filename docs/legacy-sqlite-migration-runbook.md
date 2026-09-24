# Legacy SQLite migration runbook

## Scope

This runbook imports the retained SQLite records into PostgreSQL without replacing existing PostgreSQL rows.

The container does not run migrations or seeders during startup.

The Compose runtime stores maintenance state in PostgreSQL, so container recreation does not remove an import write freeze.

Keep `database/database.sqlite` unchanged after every step.

## Test the PostgreSQL path

Create the disposable database once:

```powershell
docker compose exec postgres createdb -U sweq sweq_transports_test
```

Run the isolated PostgreSQL suite:

```powershell
docker compose exec app php vendor/bin/phpunit -c phpunit.postgresql.xml --do-not-cache-result
```

The profile rejects any database name that does not end with `_test`.

## Prepare the operational database

Rebuild the app after source or dependency changes:

```powershell
docker compose up -d --build
```

Apply migrations explicitly:

```powershell
docker compose exec app php artisan migrate --force
```

Run the import preflight without writes:

```powershell
docker compose exec app php artisan legacy-sqlite:import --source=/var/www/html/database/database.sqlite
```

Review every count, checksum, schema fingerprint, and target identity.

## Create and validate the PostgreSQL backup

Create a custom-format archive inside the PostgreSQL container:

```powershell
docker compose exec postgres pg_dump -U sweq -d sweq_transports -Fc -f /tmp/sweq-pre-import.dump
docker compose exec postgres pg_restore --list /tmp/sweq-pre-import.dump
```

Restore the archive into a disposable database:

```powershell
docker compose exec postgres createdb -U sweq sweq_transports_restore_test
docker compose exec postgres pg_restore -U sweq -d sweq_transports_restore_test /tmp/sweq-pre-import.dump
docker compose exec postgres dropdb -U sweq sweq_transports_restore_test
```

Copy the validated archive into private application storage:

```powershell
New-Item -ItemType Directory -Force storage/app/private/legacy-imports
docker compose cp postgres:/tmp/sweq-pre-import.dump storage/app/private/legacy-imports/pre-import.dump
```

Create a target-bound manifest only after the restore test passes:

```powershell
docker compose exec app php artisan legacy-sqlite:backup-manifest --backup=/var/www/html/storage/app/private/legacy-imports/pre-import.dump --output=/var/www/html/storage/app/private/legacy-imports/pre-import-manifest.json --restore-tested
```

## Commit and verify the import

Run the committing import:

```powershell
docker compose exec app php artisan legacy-sqlite:import --source=/var/www/html/database/database.sqlite --commit --backup-manifest=/var/www/html/storage/app/private/legacy-imports/pre-import-manifest.json
```

The command leaves the application in maintenance mode after business records commit.

Run the seeder explicitly:

```powershell
docker compose exec app php artisan db:seed --class=DatabaseSeeder --force
```

Run verification with the run identifier printed by the import:

```powershell
docker compose exec app php artisan legacy-sqlite:verify RUN_ID
```

Successful verification restores availability when the application was previously available.

If seeding or verification fails, keep the application in maintenance mode.

Inspect the private evidence under `storage/app/private/legacy-imports` before any recovery decision.

Database restoration requires separate approval. SQLite archival or deletion also requires separate approval.
