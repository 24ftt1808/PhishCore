<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reports MODIFY type ENUM('url', 'email', 'phone', 'screenshot') NOT NULL DEFAULT 'url'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reports MODIFY type ENUM('url', 'email', 'phone') NOT NULL DEFAULT 'url'");
    }
};