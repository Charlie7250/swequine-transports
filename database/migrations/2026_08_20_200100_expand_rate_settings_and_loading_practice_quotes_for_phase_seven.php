<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_settings', function (Blueprint $table): void {
            $table->decimal('loading_practice_within_15_miles_price', 10, 2)->default('30.00')->after('two_horse_multiplier');
            $table->decimal('loading_practice_within_25_miles_price', 10, 2)->default('45.00')->after('loading_practice_within_15_miles_price');
            $table->decimal('loading_practice_within_50_miles_price', 10, 2)->default('90.00')->after('loading_practice_within_25_miles_price');
            $table->decimal('loading_practice_on_site_hourly_rate', 10, 2)->default('25.00')->after('loading_practice_within_50_miles_price');
            $table->decimal('loading_practice_livery_day_rate', 10, 2)->default('35.00')->after('loading_practice_on_site_hourly_rate');
            $table->decimal('loading_practice_livery_week_rate', 10, 2)->default('220.00')->after('loading_practice_livery_day_rate');
            $table->decimal('loading_practice_livery_fortnight_rate', 10, 2)->default('410.00')->after('loading_practice_livery_week_rate');
        });

        Schema::table('loading_practice_quotes', function (Blueprint $table): void {
            $table->foreignId('rate_setting_id')->nullable()->after('customer_id')->constrained()->restrictOnDelete();
            $table->string('handling_livery_period')->nullable()->after('on_site_hourly_rate');
            $table->unsignedInteger('handling_livery_quantity')->nullable()->after('handling_livery_period');
            $table->decimal('handling_livery_rate', 10, 2)->nullable()->after('handling_livery_quantity');
            $table->decimal('engine_total', 10, 2)->nullable()->after('is_poa');
            $table->decimal('final_total', 10, 2)->nullable()->after('engine_total');
            $table->string('manual_final_total_reason')->nullable()->after('final_total');
            $table->json('calculation_explanation')->nullable()->after('manual_final_total_reason');
        });

        $fallbackRateSettingId = DB::table('rate_settings')
            ->where('is_active', true)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('id')
            ?? DB::table('rate_settings')
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->value('id');

        if ($fallbackRateSettingId !== null) {
            DB::table('loading_practice_quotes')
                ->whereNull('rate_setting_id')
                ->update([
                    'rate_setting_id' => $fallbackRateSettingId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('loading_practice_quotes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rate_setting_id');
            $table->dropColumn([
                'handling_livery_period',
                'handling_livery_quantity',
                'handling_livery_rate',
                'engine_total',
                'final_total',
                'manual_final_total_reason',
                'calculation_explanation',
            ]);
        });

        Schema::table('rate_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'loading_practice_within_15_miles_price',
                'loading_practice_within_25_miles_price',
                'loading_practice_within_50_miles_price',
                'loading_practice_on_site_hourly_rate',
                'loading_practice_livery_day_rate',
                'loading_practice_livery_week_rate',
                'loading_practice_livery_fortnight_rate',
            ]);
        });
    }
};
