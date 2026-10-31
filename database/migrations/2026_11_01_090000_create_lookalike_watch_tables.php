<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Look-alike Watch: new website names that copy a Brunei brand (found in the public certificate logs), and a log of
 * each check so the team page can say when the last one worked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookalike_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('brand', 60);
            $table->string('source', 30)->default('crt.sh');
            $table->timestamp('certificate_issued_at')->nullable();
            $table->unsignedSmallInteger('address_points')->default(0);
            $table->string('status', 20)->default('new'); // new or dismissed
            $table->foreignId('dismissed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('lookalike_watch_runs', function (Blueprint $table) {
            $table->id();
            $table->boolean('ok');
            $table->unsignedInteger('names_checked')->default(0);
            $table->unsignedInteger('new_found')->default(0);
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookalike_watch_runs');
        Schema::dropIfExists('lookalike_domains');
    }
};