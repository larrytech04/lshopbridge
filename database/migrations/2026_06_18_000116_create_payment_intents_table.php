<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A single charge attempt against a payment provider. Bridges the
        // user action -> provider API -> webhook -> deposit/funding settlement.
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();       // PB-INT-XXXX (sent to provider)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider_code');
            $table->string('method_code')->nullable();
            $table->string('purpose')->default('deposit'); // deposit | funding

            $table->decimal('amount', 16, 2);
            $table->string('currency', 3)->default('XAF');

            // pending | processing | succeeded | failed | cancelled | expired
            $table->string('status')->default('pending');

            $table->string('provider_reference')->nullable(); // id returned by provider
            $table->string('redirect_url')->nullable();        // hosted checkout / STK push ref
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->foreignId('deposit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('funding_request_id')->nullable()->constrained()->nullOnDelete();

            $table->json('payload')->nullable();   // request snapshot (no secrets)
            $table->json('meta')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['provider_code', 'status']);
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
