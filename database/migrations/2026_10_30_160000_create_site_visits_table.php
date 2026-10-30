<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per visitor per day. The visitor is a one-way code made from the address and browser plus the
        // day, so it cannot be turned back into a person and is different again tomorrow. No address is stored.
        // (The code is made in App\Http\Middleware\CountSiteVisit.)
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('visitor_hash', 32);
            $table->boolean('is_member')->default(false);
            $table->unsignedInteger('views')->default(1);
            $table->timestamp('created_at')->nullable();

            $table->unique(['day', 'visitor_hash']);
            $table->index('day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
