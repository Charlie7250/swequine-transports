# Local development

## Running the app

The app runs under Docker Compose:

```
docker compose up --build
```

This starts two services:

- `app`: PHP 8.5 running `php artisan serve` on port 8000 (http://localhost:8000)
- `postgres`: PostgreSQL 16 with the `sweq_transports` database

Standard tests use in-memory SQLite through `phpunit.xml`.
On the current Windows host, run `composer run test:host`.
In the app container, run `docker compose exec app php vendor/bin/phpunit --do-not-cache-result`.
The command loads the installed `pdo_sqlite` and `sqlite3` extensions for that test process only.
It does not install extensions or edit the host PHP settings.
SQLite test success does not establish PostgreSQL compatibility.

PostgreSQL integration tests use `phpunit.postgresql.xml` and `sweq_transports_test`.
Run them inside the app container.

```
docker compose exec app php vendor/bin/phpunit -c phpunit.postgresql.xml --do-not-cache-result
```

The profile rejects operational database names.
Container startup never runs migrations or seeders.
Run both operations explicitly when the task requires them.

## Source mounts

The app service mounts editable source directories without replacing the container environment or dependencies.

```yaml
  app:
    # ...
    volumes:
      - ./app:/var/www/html/app
      - ./config:/var/www/html/config
      - ./database:/var/www/html/database
      - ./resources:/var/www/html/resources
      - ./routes:/var/www/html/routes
      - ./storage/app/private:/var/www/html/storage/app/private
      - ./tests:/var/www/html/tests
      - ./phpunit.postgresql.xml:/var/www/html/phpunit.postgresql.xml:ro
```

- Blade views (`resources/views`), PHP (`app`), routes, config, and migrations/seeders are
  read live; Blade recompiles per request in `local`.
- The container keeps its own `.env` (env values come from the `environment:` block in
  `compose.yaml`, e.g. `DB_HOST: postgres`), its baked `vendor/`, its compiled Vite assets
  in `public/build`, and its `bootstrap/cache`.

Front-end assets are compiled in the image's Vite build stage, so changing JS/CSS still
needs an image rebuild (`docker compose up --build`). Editing Blade or PHP does not.

Recreate the container after changing mount definitions.
PHP and Blade edits then appear on the next request.

See `docs/legacy-sqlite-migration-runbook.md` for the controlled import procedure.
