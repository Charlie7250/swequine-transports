<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('postcode', 16)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('current_working_revision_id')->nullable();
            $table->unsignedBigInteger('issued_revision_id')->nullable();
            $table->unsignedBigInteger('accepted_revision_id')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('weekly_fuel_prices', function (Blueprint $table): void {
            $table->id();
            $table->date('week_commencing');
            $table->string('source');
            $table->decimal('price_per_litre_inc_vat', 8, 4);
            $table->boolean('is_active')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rate_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('depot_postcode', 16);
            $table->decimal('miles_per_gallon', 8, 4);
            $table->decimal('litres_per_gallon', 8, 4);
            $table->decimal('maintenance_per_mile', 10, 6);
            $table->decimal('unloaded_add_on_per_mile', 10, 6);
            $table->decimal('loaded_add_on_per_mile', 10, 6);
            $table->decimal('two_horse_multiplier', 10, 6)->nullable();
            $table->boolean('is_active')->default(false);
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->timestamps();
        });

        Schema::create('job_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weekly_fuel_price_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rate_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('revision_number');
            $table->unsignedTinyInteger('horse_count')->default(1);
            $table->string('depot_postcode_override', 16)->nullable();
            $table->string('pickup_postcode', 16)->nullable();
            $table->string('dropoff_postcode', 16)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('engine_total', 10, 2)->default(0);
            $table->decimal('final_total', 10, 2)->nullable();
            $table->string('manual_final_total_reason')->nullable();
            $table->json('calculation_explanation')->nullable();
            $table->timestamps();

            $table->unique(['job_id', 'revision_number']);
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->foreign('current_working_revision_id')
                ->references('id')
                ->on('job_revisions')
                ->nullOnDelete();
            $table->foreign('issued_revision_id')
                ->references('id')
                ->on('job_revisions')
                ->nullOnDelete();
            $table->foreign('accepted_revision_id')
                ->references('id')
                ->on('job_revisions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropForeign(['current_working_revision_id']);
            $table->dropForeign(['issued_revision_id']);
            $table->dropForeign(['accepted_revision_id']);
        });

        Schema::dropIfExists('job_revisions');
        Schema::dropIfExists('rate_settings');
        Schema::dropIfExists('weekly_fuel_prices');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('customers');
    }
};
