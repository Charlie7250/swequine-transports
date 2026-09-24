<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_resolution_legs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('route_resolution_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->string('leg_type');
            $table->string('origin_input');
            $table->string('destination_input');
            $table->json('resolved_origin_metadata')->nullable();
            $table->json('resolved_destination_metadata')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('distance_metres')->nullable();
            $table->unsignedInteger('quoted_miles')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('provider_route_id')->nullable();
            $table->json('warnings')->nullable();
            $table->json('failure_detail')->nullable();
            $table->timestamps();

            $table->unique(['route_resolution_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_resolution_legs');
    }
};
