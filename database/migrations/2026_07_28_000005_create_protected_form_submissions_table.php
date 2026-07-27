<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lean, append-only analytics ledger — one row per FormProtectionService
 * evaluation, regardless of outcome. This is the only table that can answer
 * "submissions today across every protected form" honestly, since accepted
 * submissions land in different destination tables per form (Dispute,
 * Review, NewsletterSubscriber, ReferralLead, GuestSupportTicket...). Holds
 * no content, matching LoginAttempt's immutable-log pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protected_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('form_type');
            $table->string('outcome'); // accepted | challenged | held | silently_discarded | rate_limited | turnstile_failed | error
            $table->string('risk_level')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['form_type', 'created_at']);
            $table->index(['outcome', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protected_form_submissions');
    }
};
