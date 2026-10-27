<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // A short note about what the page looked like after it loaded in the urlscan.io sandbox.
            // Display only: it is never used for the score or the verdict.
            $table->text('preview_note')->nullable()->after('preview_path');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('preview_note');
        });
    }
};