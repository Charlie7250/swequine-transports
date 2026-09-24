<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_revisions', function (Blueprint $table): void {
            $table->foreignId('route_resolution_id')
                ->nullable()
                ->after('rate_setting_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_revisions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('route_resolution_id');
        });
    }
};
