<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funding_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();       // PB-FND-XXXX
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_account_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot of recipient (kept even if beneficiary later edited/deleted)
            $table->string('app_type');                  // alipay | wechat | other
            $table->string('recipient_name');
            $table->string('recipient_account');

            // Money math (all snapshotted at request time)
            $table->decimal('source_amount', 16, 2);     // debited from wallet (base ccy)
            $table->string('source_currency', 3)->default('XAF');
            $table->decimal('exchange_rate', 20, 8);     // base -> target
            $table->decimal('target_amount', 16, 2);     // delivered to recipient
            $table->string('target_currency', 3)->default('CNY');
            $table->decimal('fee', 16, 2)->default(0);
            $table->decimal('total_charged', 16, 2);     // source_amount + fee

            // How it is paid: existing wallet balance, or a fresh direct payment
            $table->string('funding_source')->default('wallet'); // wallet | direct_payment
            $table->foreignId('deposit_id')->nullable()->constrained()->nullOnDelete();

            /*
             * Lifecycle status:
             * payment_pending | payment_successful | funding_processing |
             * funding_successful | funding_failed | refunded | manual_review
             */
            $table->string('status')->default('payment_pending');
            $table->boolean('risk_flagged')->default(false);
            $table->string('manual_review_reason')->nullable();

            $table->string('provider_code')->nullable();      // funding provider used
            $table->string('provider_reference')->nullable();  // funding txn id
            $table->string('receipt_path')->nullable();        // PRIVATE disk (admin/provider receipt)

            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funding_requests');
    }
};
