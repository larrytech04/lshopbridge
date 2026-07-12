<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable double-entry style ledger. Every wallet balance change
        // MUST go through a row here (see App\Services\Wallet\WalletService).
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');                  // credit | debit
            $table->string('category')->default('general'); // deposit | funding | refund | fee | adjustment | hold | release
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('currency', 3)->default('XAF');
            $table->string('description')->nullable();

            // Polymorphic source (Deposit, FundingRequest, manual adjustment...)
            $table->nullableMorphs('source');

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
