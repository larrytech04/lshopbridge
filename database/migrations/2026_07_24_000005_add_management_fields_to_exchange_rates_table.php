<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            // Only "percentage" (rate * (1 - margin_percent/100)) was ever implemented.
            // These add the other two margin types the workspace supports without
            // touching the existing percentage column/behavior.
            $table->string('margin_type')->default('percentage')->after('margin_percent');
            $table->decimal('margin_fixed', 20, 8)->nullable()->after('margin_type');
            $table->decimal('custom_effective_rate', 20, 8)->nullable()->after('margin_fixed');

            // No automatic FX provider is connected anywhere in this platform — every
            // rate is manually entered. This field is real and stored for when one
            // eventually is, but 'provider' rows are never auto-synced today.
            $table->string('rate_source')->default('manual')->after('custom_effective_rate');

            $table->decimal('min_amount', 16, 2)->nullable()->after('rate_source');
            $table->decimal('max_amount', 16, 2)->nullable()->after('min_amount');
            $table->text('notes')->nullable()->after('max_amount');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'margin_type', 'margin_fixed', 'custom_effective_rate',
                'rate_source', 'min_amount', 'max_amount', 'notes',
            ]);
        });
    }
};
