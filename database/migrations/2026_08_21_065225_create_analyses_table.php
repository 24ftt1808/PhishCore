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
    Schema::create('analyses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('report_id')->constrained()->cascadeOnDelete();
        $table->json('whois_data')->nullable();
        $table->integer('domain_age_days')->nullable();
        $table->float('url_syntax_score')->nullable();
        $table->string('ip_address')->nullable();
        $table->string('ip_reputation')->nullable();
        $table->json('redirect_chain')->nullable();
        $table->enum('verdict', ['phishing', 'suspicious', 'clean'])->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('analyses');
}
};
