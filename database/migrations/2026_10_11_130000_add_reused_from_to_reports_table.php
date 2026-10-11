<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Set when the result was copied from an earlier scan of the same link instead of a fresh check.
            $table->foreignId('reused_from_report_id')->nullable()->after('status')
                ->constrained('reports')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reused_from_report_id');
        });
    }
};
