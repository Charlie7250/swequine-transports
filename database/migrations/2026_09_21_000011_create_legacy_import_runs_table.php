<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->text('source_path');
            $table->string('source_checksum', 64);
            $table->string('source_schema_fingerprint', 64);
            $table->json('target_identity');
            $table->string('target_schema_fingerprint', 64);
            $table->string('importer_version');
            $table->string('defaults_version');
            $table->string('phase');
            $table->string('status');
            $table->boolean('is_dry_run')->default(true);
            $table->boolean('was_maintenance_active')->default(false);
            $table->text('source_snapshot_path')->nullable();
            $table->string('source_snapshot_checksum', 64)->nullable();
            $table->text('backup_manifest_path')->nullable();
            $table->string('backup_checksum', 64)->nullable();
            $table->text('report_path')->nullable();
            $table->json('counts')->nullable();
            $table->json('identifier_mappings')->nullable();
            $table->string('mapping_checksum', 64)->nullable();
            $table->json('verification_results')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['source_checksum', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_runs');
    }
};
