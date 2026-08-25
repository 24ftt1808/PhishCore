<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('type', ['url', 'email', 'phone'])->default('url')->after('user_id');
            $table->string('phone_number')->nullable()->after('sender_email');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['type', 'phone_number']);
        });
    }
};