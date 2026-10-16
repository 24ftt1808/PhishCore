<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // which scanner tab opens first
            $table->string('default_scan_tab', 12)->default('url')->after('floating_chat_enabled');
            // null = keep scans forever
            $table->unsignedSmallInteger('scan_retention_days')->nullable()->after('default_scan_tab');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['default_scan_tab', 'scan_retention_days']);
        });
    }
};
