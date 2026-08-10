<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX weekly_fuel_prices_single_active_idx ON weekly_fuel_prices (is_active) WHERE is_active = true');
            DB::statement('CREATE UNIQUE INDEX rate_settings_single_active_idx ON rate_settings (is_active) WHERE is_active = true');
        }

        if ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX weekly_fuel_prices_single_active_idx ON weekly_fuel_prices (is_active) WHERE is_active = 1');
            DB::statement('CREATE UNIQUE INDEX rate_settings_single_active_idx ON rate_settings (is_active) WHERE is_active = 1');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS weekly_fuel_prices_single_active_idx');
            DB::statement('DROP INDEX IF EXISTS rate_settings_single_active_idx');
        }
    }
};
