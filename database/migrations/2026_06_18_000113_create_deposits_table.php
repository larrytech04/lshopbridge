<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();      // PB-DEP-XXXX
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_code')->nullable(); // automation provider used

            $table->decimal('amount', 16, 2);            // gross paid
            $table->decimal('fee', 16, 2)->default(0);
            $table->decimal('net_amount', 16, 2);        // credited to wallet
            $table->string('currency', 3)->default('XAF');

            // pending | under_review | processing | confirmed | rejected | failed
            $table->string('status')->default('pending');

            $table->boolean('is_automated')->default(false);
            $table->boolean('risk_flagged')->default(false);

            $table->string('proof_path')->nullable();    // PRIVATE disk (manual flow)
            $table->string('provider_reference')->nullable(); // charge id from provider
            $table->json('payer_details')->nullable();   // phone, name, network...
            $table->json('meta')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
