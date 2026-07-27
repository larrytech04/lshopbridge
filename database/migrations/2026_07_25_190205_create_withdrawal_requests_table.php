<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saved_payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destination_label');           // snapshot, survives edits/deletes of the saved method
            $table->string('destination_account_ref')->nullable();
            $table->decimal('amount', 14, 2);
            $table->decimal('fee', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->string('currency', 3)->default('XAF');
            $table->string('status')->default('pending'); // pending|approved|processing|paid|rejected|cancelled
            $table->text('rejection_reason')->nullable();
            $table->string('payout_reference')->nullable(); // provider/mobile-money transaction id, filled by admin
            $table->timestamp('pin_confirmed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
