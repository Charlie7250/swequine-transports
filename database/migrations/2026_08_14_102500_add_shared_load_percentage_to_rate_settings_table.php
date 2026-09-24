<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_settings', function (Blueprint $table): void {
            $table->decimal('shared_load_percentage', 10, 6)->default('0.750000');
        });
    }

    public function down(): void
    {
        Schema::table('rate_settings', function (Blueprint $table): void {
            $table->dropColumn('shared_load_percentage');
        });
    }
};
