<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'takedown_requested', 'takedown_confirmed']);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_status_logs');
    }
};