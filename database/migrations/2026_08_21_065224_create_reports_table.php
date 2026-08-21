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
    Schema::create('reports', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('url');
        $table->string('screenshot_path')->nullable();
        $table->string('sender_email')->nullable();
        $table->text('description')->nullable();
        $table->enum('status', ['active', 'investigating', 'takedown_requested', 'completed'])->default('active');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('reports');
}
};
