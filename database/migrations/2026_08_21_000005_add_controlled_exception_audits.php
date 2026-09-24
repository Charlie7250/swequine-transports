<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_resolutions', function (Blueprint $table): void {
            $table->foreignId('previous_route_resolution_id')
                ->nullable()
                ->after('transport_enquiry_id')
                ->constrained('route_resolutions')
                ->nullOnDelete();
            $table->string('attempt_kind')->default('initial')->after('route_profile');
        });

        Schema::create('quote_exception_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_leg_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('route_resolution_leg_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('exception_type');
            $table->string('reason_category');
            $table->text('explanation');
            $table->string('original_value')->nullable();
            $table->string('replacement_value');
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_exception_audits');

        Schema::table('route_resolutions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('previous_route_resolution_id');
            $table->dropColumn('attempt_kind');
        });
    }
};
