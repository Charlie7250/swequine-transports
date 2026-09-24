<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transport_enquiry_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('provider_product')->nullable();
            $table->string('request_id')->nullable();
            $table->string('route_profile')->nullable();
            $table->string('overall_status')->default('pending');
            $table->boolean('operator_action_required')->default(false);
            $table->boolean('pricing_eligible')->default(false);
            $table->json('raw_input_snapshot')->nullable();
            $table->json('request_context')->nullable();
            $table->json('normalisation_metadata')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->json('warnings')->nullable();
            $table->json('failure_detail')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('request_started_at')->nullable();
            $table->timestamp('response_received_at')->nullable();
            $table->unsignedInteger('latency_milliseconds')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_resolutions');
    }
};
