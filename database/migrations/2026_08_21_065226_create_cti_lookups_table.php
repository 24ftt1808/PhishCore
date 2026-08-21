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
    Schema::create('cti_lookups', function (Blueprint $table) {
        $table->id();
        $table->foreignId('report_id')->constrained()->cascadeOnDelete();
        $table->string('source'); // e.g. "VirusTotal"
        $table->json('raw_response')->nullable();
        $table->float('threat_score')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('cti_lookups');
}
};
