<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tamper-evident-adjacent log of bot/spam-protection decisions across every
 * protected form. Deliberately separate from `risk_flags` (money/fraud risk
 * tied to a user's financial activity) since this is form-abuse specific and
 * the spec calls for its own "Security Events" surface under Trust & Safety.
 * Never stores raw message content or a real IP — only a salted hash — so a
 * leaked table can't be used to deanonymize visitors or read spam content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_security_events', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // FSE-XXXXXXXX
            $table->string('event_type');          // form.honeypot_triggered, form.turnstile_failed, ...
            $table->string('form_type');            // contact, registration, login, newsletter, ...
            $table->string('risk_level');           // low | medium | high | critical
            $table->string('action_taken');         // allowed | challenged | held | silently_discarded | rate_limited | temporarily_restricted
            $table->json('triggered_rules')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('payload_fingerprint', 64)->nullable();
            $table->text('note')->nullable(); // redacted, safe summary only — never full form content
            $table->nullableMorphs('related');
            $table->string('status')->default('logged'); // logged | false_positive
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['form_type', 'created_at']);
            $table->index(['risk_level', 'created_at']);
            $table->index('ip_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_security_events');
    }
};
