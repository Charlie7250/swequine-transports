<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_revisions', function (Blueprint $table): void {
            $table->timestamp('issued_at')->nullable()->after('calculation_explanation');
            $table->foreignId('issued_by_user_id')->nullable()->after('issued_at')->constrained('users')->nullOnDelete();
            $table->string('issue_reference')->nullable()->after('issued_by_user_id');
            $table->json('issued_evidence')->nullable()->after('issue_reference');
        });
    }

    public function down(): void
    {
        Schema::table('job_revisions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('issued_by_user_id');
            $table->dropColumn(['issued_at', 'issue_reference', 'issued_evidence']);
        });
    }
};
