<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', ['active', 'investigating', 'takedown_requested', 'completed', 'processing', 'failed'])->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', ['active', 'investigating', 'takedown_requested', 'completed'])->default('active')->change();
        });
    }
};
