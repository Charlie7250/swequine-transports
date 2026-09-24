<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_days', function (Blueprint $table): void {
            $table->id();
            $table->date('run_date');
            $table->string('name')->nullable();
            $table->string('depot_postcode', 16)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->foreignId('transport_day_id')->nullable()->constrained('transport_days')->nullOnDelete();
            $table->unsignedInteger('transport_day_sequence')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('transport_day_id');
            $table->dropColumn('transport_day_sequence');
        });

        Schema::dropIfExists('transport_days');
    }
};
