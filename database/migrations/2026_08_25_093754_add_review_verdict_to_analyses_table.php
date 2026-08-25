<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE analyses MODIFY verdict ENUM('clean', 'suspicious', 'phishing', 'review') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE analyses MODIFY verdict ENUM('clean', 'suspicious', 'phishing') NOT NULL");
    }
};