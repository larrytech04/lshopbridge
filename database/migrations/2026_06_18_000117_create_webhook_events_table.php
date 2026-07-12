<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every inbound provider webhook is logged here BEFORE processing.
        // (provider_code, event_id) uniqueness gives idempotency / dedupe.
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code');
            $table->string('event_id')->nullable();   // provider's event/txn id
            $table->string('event_type')->nullable();
            $table->string('reference')->nullable();  // our intent/deposit reference

            // received | processed | duplicate | invalid_signature | failed | ignored
            $table->string('status')->default('received');

            $table->boolean('signature_valid')->default(false);
            $table->nullableMorphs('related');        // Deposit / FundingRequest / PaymentIntent
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider_code', 'event_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
