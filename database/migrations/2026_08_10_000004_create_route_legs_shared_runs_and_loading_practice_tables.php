<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_legs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_revision_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->string('label');
            $table->string('start_postcode', 16)->nullable();
            $table->string('end_postcode', 16)->nullable();
            $table->unsignedInteger('miles');
            $table->unsignedInteger('manual_miles')->nullable();
            $table->string('rate_type');
            $table->decimal('rate_per_mile', 10, 6)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['job_revision_id', 'sequence']);
        });

        Schema::create('shared_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->date('run_date')->nullable();
            $table->string('depot_postcode', 16)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('shared_run_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shared_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_revision_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('full_charge_miles')->default(0);
            $table->unsignedInteger('split_charge_miles')->default(0);
            $table->decimal('total_charge', 10, 2)->default(0);
            $table->json('allocation_explanation')->nullable();
            $table->timestamps();
        });

        Schema::create('loading_practice_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->string('distance_band')->nullable();
            $table->unsignedInteger('travel_miles')->nullable();
            $table->decimal('package_price', 10, 2)->nullable();
            $table->decimal('on_site_hours', 5, 2)->nullable();
            $table->decimal('on_site_hourly_rate', 10, 2)->nullable();
            $table->boolean('is_poa')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_practice_quotes');
        Schema::dropIfExists('shared_run_allocations');
        Schema::dropIfExists('shared_runs');
        Schema::dropIfExists('route_legs');
    }
};
