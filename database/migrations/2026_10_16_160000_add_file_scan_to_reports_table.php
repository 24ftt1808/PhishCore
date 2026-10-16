<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('type', ['url', 'email', 'phone', 'screenshot', 'file'])->default('url')->change();
        });

        Schema::table('reports', function (Blueprint $table) {
            // The uploaded file itself is never kept. Only its name and fingerprint are.
            $table->string('file_name')->nullable()->after('preview_path');
            $table->string('file_sha256', 64)->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'file_sha256']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->enum('type', ['url', 'email', 'phone', 'screenshot'])->default('url')->change();
        });
    }
};
