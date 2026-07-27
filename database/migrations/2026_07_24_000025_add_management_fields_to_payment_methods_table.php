<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            // draft | active | disabled | archived — is_active stays in sync for backward compatibility.
            $table->string('status')->default('active')->after('is_active');
            $table->json('currencies')->nullable()->after('currency'); // additional/display list; `currency` remains the real transaction currency
            $table->boolean('deposit_enabled')->default(true)->after('is_automated');
            $table->boolean('marketplace_enabled')->default(true)->after('deposit_enabled');
            $table->boolean('refund_support')->default(true)->after('marketplace_enabled');
            $table->boolean('partial_refund_support')->default(false)->after('refund_support');
            $table->boolean('requires_manual_review')->default(false)->after('requires_proof');
            $table->unsignedTinyInteger('kyc_level_required')->nullable()->after('requires_manual_review');
            $table->string('processing_time_estimate')->nullable()->after('kyc_level_required');
            $table->timestamp('available_from')->nullable()->after('processing_time_estimate');
            $table->timestamp('available_until')->nullable()->after('available_from');
            $table->text('admin_notes')->nullable()->after('available_until');
            $table->foreignId('updated_by')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'status', 'currencies', 'deposit_enabled', 'marketplace_enabled', 'refund_support',
                'partial_refund_support', 'requires_manual_review', 'kyc_level_required',
                'processing_time_estimate', 'available_from', 'available_until', 'admin_notes',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
