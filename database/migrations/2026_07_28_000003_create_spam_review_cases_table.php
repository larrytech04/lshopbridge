<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Medium-confidence submissions held for human review rather than silently
 * discarded. `safe_payload` stores only the fields a reviewer needs to judge
 * legitimacy (name/email/subject/message) — never used for login, password
 * reset, or any form carrying credentials/secrets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spam_review_cases', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // SRC-XXXXXXXX
            $table->string('form_type');
            $table->string('status')->default('pending_review'); // pending_review | legitimate | spam | silently_discarded | released | archived
            $table->string('risk_level');
            $table->unsignedTinyInteger('risk_score')->nullable();
            $table->json('triggered_rules')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->json('safe_payload')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('fingerprint_hash', 64)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('form_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spam_review_cases');
    }
};
