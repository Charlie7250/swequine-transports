<?php

use Tests\Support\PostgresTestDatabaseGuard;

require dirname(__DIR__).'/vendor/autoload.php';

$profile = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'pgsql',
    'DB_HOST' => 'postgres',
    'DB_PORT' => '5432',
    'DB_DATABASE' => 'sweq_transports_test',
    'DB_USERNAME' => 'sweq',
    'DB_PASSWORD' => 'sweq',
    'DB_URL' => '',
    'POSTGRES_INTEGRATION_TEST' => 'true',
    'LARAVEL_STORAGE_PATH' => '/tmp/sweq-transports-postgres-tests',
];

foreach ($profile as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

foreach (['app/private', 'framework/cache', 'framework/sessions', 'framework/views', 'logs'] as $path) {
    $directory = $profile['LARAVEL_STORAGE_PATH'].'/'.$path;
    if (! is_dir($directory)) {
        mkdir($directory, 0700, true);
    }
}

PostgresTestDatabaseGuard::assertSafe($profile);
