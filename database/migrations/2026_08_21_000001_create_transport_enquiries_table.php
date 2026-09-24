<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_enquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('source')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('pickup_postcode', 16)->nullable();
            $table->string('dropoff_postcode', 16)->nullable();
            $table->unsignedTinyInteger('horse_count')->nullable();
            $table->date('requested_date')->nullable();
            $table->boolean('date_to_be_arranged')->default(false);
            $table->text('special_constraints')->nullable();
            $table->boolean('special_constraints_acknowledged')->default(false);
            $table->string('status')->default('draft');
            $table->foreignId('quote_job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_enquiries');
    }
};
