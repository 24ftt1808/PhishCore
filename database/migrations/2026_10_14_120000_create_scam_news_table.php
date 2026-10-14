<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The "Latest Scam News" box on the dashboard. Articles come from RSS feeds (news:fetch, once a day)
        // or are added by hand on the Scam News admin page.
        Schema::create('scam_news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('url', 2048);
            // A fixed-length fingerprint of the link, so the same article is never saved twice
            // (a unique index straight on a 2,048-character link is too long for MySQL).
            $table->string('url_hash', 40)->unique();
            $table->string('source', 120);
            $table->string('image_url', 2048)->nullable();
            $table->timestamp('published_at')->index();
            $table->boolean('is_visible')->default(true);
            // Added by hand: the daily clean-up never deletes these.
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scam_news');
    }
};
