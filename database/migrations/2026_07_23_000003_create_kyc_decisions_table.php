<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable per-case decision log. A previous decision is never edited or deleted;
        // every action (approve, request more info, reject, escalate, etc.) appends a new
        // row, giving the review workspace a true timeline and the analytics panel real
        // reviewer-performance data.
        Schema::create('kyc_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_verification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision_type'); // approve | approve_limited | request_more_info | return_for_correction | reject | escalate | hold | flag_suspicious | freeze_account
            $table->foreignId('reason_template_id')->nullable()->constrained('kyc_decision_templates')->nullOnDelete();
            $table->text('internal_reason')->nullable();
            $table->text('customer_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['kyc_verification_id', 'created_at']);
            $table->index('decision_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_decisions');
    }
};
