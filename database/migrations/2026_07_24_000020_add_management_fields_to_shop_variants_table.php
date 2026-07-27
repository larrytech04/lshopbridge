<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_variants', function (Blueprint $table) {
            $table->decimal('cost_price', 14, 2)->nullable()->after('price');
            $table->decimal('sale_price', 14, 2)->nullable()->after('compare_at_price');
            $table->timestamp('sale_starts_at')->nullable()->after('sale_price');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
            $table->string('barcode')->nullable()->after('sku');
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock');
            $table->string('external_id')->nullable()->after('low_stock_threshold');
            $table->string('provider_status')->nullable()->after('external_id');
        });
    }

    public function down(): void
    {
        Schema::table('shop_variants', function (Blueprint $table) {
            $table->dropColumn([
                'cost_price', 'sale_price', 'sale_starts_at', 'sale_ends_at',
                'barcode', 'low_stock_threshold', 'external_id', 'provider_status',
            ]);
        });
    }
};
