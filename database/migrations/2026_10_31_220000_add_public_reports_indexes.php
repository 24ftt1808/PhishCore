<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the public Reports page, which looks up "completed reports that have a suspicious or phishing result"
 * several times per visit. Without them every visit reads every report and every result.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->index(['report_id', 'verdict'], 'analyses_report_verdict_index');
            $table->index(['verdict', 'report_id'], 'analyses_verdict_report_index');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'reports_status_created_index');
            $table->index(['type', 'status'], 'reports_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table) {
            $table->dropIndex('analyses_report_verdict_index');
            $table->dropIndex('analyses_verdict_report_index');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('reports_status_created_index');
            $table->dropIndex('reports_type_status_index');
        });
    }
};