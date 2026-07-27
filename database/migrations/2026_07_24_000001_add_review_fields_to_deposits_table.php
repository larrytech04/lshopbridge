<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('confirmed_by')->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable()->after('rejection_reason');
            $table->boolean('flagged_for_investigation')->default(false)->after('risk_flagged');

            // No external bank/settlement feed exists to diff against — this is a
            // computed proxy (see DepositService::reconciliationStatus()), not a claim
            // about actual provider/bank settlement records.
            $table->string('reconciliation_status')->nullable()->after('status');
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_status');
            $table->foreignId('reconciled_by')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
            $table->text('reconciliation_note')->nullable()->after('reconciled_by');

            $table->string('refund_reference')->nullable()->after('reconciliation_note');
            $table->text('refund_reason')->nullable()->after('refund_reference');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');
            $table->foreignId('refunded_by')->nullable()->after('refunded_at')->constrained('users')->nullOnDelete();

            $table->text('reversal_reason')->nullable()->after('refunded_by');
            $table->timestamp('reversed_at')->nullable()->after('reversal_reason');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();

            $table->index('reconciliation_status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex(['reconciliation_status']);
            $table->dropIndex(['assigned_to']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn([
                'admin_notes', 'flagged_for_investigation',
                'reconciliation_status', 'reconciled_at', 'reconciliation_note',
                'refund_reference', 'refund_reason', 'refunded_at',
                'reversal_reason', 'reversed_at',
            ]);
        });
    }
};
