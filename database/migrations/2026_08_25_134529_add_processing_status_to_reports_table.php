<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reports MODIFY status ENUM('active', 'investigating', 'takedown_requested', 'completed', 'processing', 'failed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reports MODIFY status ENUM('active', 'investigating', 'takedown_requested', 'completed') NOT NULL DEFAULT 'active'");
    }
};