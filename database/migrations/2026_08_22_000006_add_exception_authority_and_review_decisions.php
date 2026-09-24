<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('can_manage_quote_exceptions')->default(false)->after('password');
        });

        Schema::table('quote_exception_audits', function (Blueprint $table): void {
            $table->foreignId('source_quote_exception_audit_id')
                ->nullable()
                ->after('job_revision_id')
                ->constrained('quote_exception_audits')
                ->nullOnDelete();
        });

        Schema::create('route_resolution_review_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('route_resolution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('decision');
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->unique(['route_resolution_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_resolution_review_decisions');

        Schema::table('quote_exception_audits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('source_quote_exception_audit_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('can_manage_quote_exceptions');
        });
    }
};
