<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->boolean('risk_flagged')->default(false)->after('meta');
            $table->text('manual_review_reason')->nullable()->after('risk_flagged');
            $table->string('tracking_number')->nullable()->after('manual_review_reason');
            $table->string('carrier')->nullable()->after('tracking_number');
            $table->timestamp('shipped_at')->nullable()->after('carrier');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
            $table->text('cancel_reason')->nullable()->after('cancelled_at');
            $table->text('admin_notes')->nullable()->after('cancel_reason');
            $table->foreignId('assigned_to')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();

            $table->index('risk_flagged');
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn([
                'risk_flagged', 'manual_review_reason', 'tracking_number', 'carrier',
                'shipped_at', 'delivered_at', 'cancelled_at', 'cancel_reason', 'admin_notes',
            ]);
        });
    }
};
