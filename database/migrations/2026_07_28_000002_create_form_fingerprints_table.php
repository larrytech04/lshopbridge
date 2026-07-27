<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Content-based fingerprint of a submission's safe/normalized fields (never
 * raw passwords, codes, or card data — see FormFingerprintService). Tracked
 * independent of form_type so "same payload submitted to multiple forms" is
 * detectable; `form_types`/`ip_hashes` are small capped JSON arrays used as
 * an approximate distinct-count, not an exhaustive audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint_hash', 64)->unique();
            $table->json('form_types');       // capped list of form_type values seen
            $table->json('ip_hashes');        // capped list of distinct ip_hash values seen
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->boolean('blocked')->default(false);
            $table->timestamps();

            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fingerprints');
    }
};
