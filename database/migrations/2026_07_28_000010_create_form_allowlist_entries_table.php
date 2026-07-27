<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * False-positive protection: matching entries skip the Turnstile/honeypot/
 * timing/rate-limit challenge for a request, but CSRF, validation,
 * authorization, and injection protection are untouched by this table —
 * see FormProtectionService::guard(), which only reads this to decide
 * whether to run the *bot-challenge* layers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_allowlist_entries', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type'); // ip | email_domain
            $table->string('subject_value');
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable(); // null = no expiry
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_type', 'subject_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_allowlist_entries');
    }
};
