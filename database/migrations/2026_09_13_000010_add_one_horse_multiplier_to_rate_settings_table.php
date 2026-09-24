<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_settings', function (Blueprint $table): void {
            $table->decimal('one_horse_multiplier', 10, 6)->nullable()->after('loaded_add_on_per_mile');
        });
    }

    public function down(): void
    {
        Schema::table('rate_settings', function (Blueprint $table): void {
            $table->dropColumn('one_horse_multiplier');
        });
    }
};
