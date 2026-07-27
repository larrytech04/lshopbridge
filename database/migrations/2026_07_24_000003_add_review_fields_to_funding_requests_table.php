<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funding_requests', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('processed_by')->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable()->after('notes');
            $table->boolean('flagged_for_investigation')->default(false)->after('risk_flagged');

            // Computed proxy — no external settlement feed exists to diff against.
            $table->string('reconciliation_status')->nullable()->after('status');
            $table->timestamp('reconciled_at')->nullable()->after('reconciliation_status');
            $table->foreignId('reconciled_by')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
            $table->text('reconciliation_note')->nullable()->after('reconciled_by');

            $table->text('cancellation_reason')->nullable()->after('reconciliation_note');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();

            $table->index('reconciliation_status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('funding_requests', function (Blueprint $table) {
            $table->dropIndex(['reconciliation_status']);
            $table->dropIndex(['assigned_to']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('reconciled_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'admin_notes', 'flagged_for_investigation',
                'reconciliation_status', 'reconciled_at', 'reconciliation_note',
                'cancellation_reason', 'cancelled_at',
            ]);
        });
    }
};
