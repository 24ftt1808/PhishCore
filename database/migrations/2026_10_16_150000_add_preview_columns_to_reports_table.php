<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // The link found in an email, kept only so a page preview can be offered for it.
            $table->text('preview_url')->nullable()->after('screenshot_path');
            // The saved page-preview picture for screenshot and email scans (link scans use screenshot_path).
            $table->string('preview_path')->nullable()->after('preview_url');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['preview_url', 'preview_path']);
        });
    }
};
