<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Addresses that community lists (OpenPhish, URLhaus, PhishTank) currently report as phishing or
        // malware. feeds:update refreshes them; an address that drops off a list is removed at the next run.
        Schema::create('threat_urls', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40);
            // A fixed-length fingerprint of the normalised address (see ThreatFeeds::normalizeUrl), so an
            // exact match is one indexed lookup and the same address is never saved twice per list.
            $table->string('url_hash', 40);
            $table->string('url', 2048);
            $table->string('host', 255);
            // The part of the host one owner registered ("bibd.com.bn"), for "another page on this site is listed".
            $table->string('registrable', 255)->index();
            $table->timestamp('seen_at')->index();
            $table->timestamps();

            $table->unique(['source', 'url_hash']);
            $table->index('url_hash');
        });

        // The most visited sites in the world (Tranco list), by position (1 = most visited). A well-known site is not a fresh scam
        // site, so it calms the rules that only look at how a name is spelled.
        Schema::create('popular_domains', function (Blueprint $table) {
            $table->string('domain', 255)->primary();
            $table->unsignedInteger('position')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popular_domains');
        Schema::dropIfExists('threat_urls');
    }
};
