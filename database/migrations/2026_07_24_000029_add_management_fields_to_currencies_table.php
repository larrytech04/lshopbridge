<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('thousands_separator', 4)->default(',')->after('decimals');
            $table->string('decimal_separator', 4)->default('.')->after('thousands_separator');
            $table->boolean('wallet_enabled')->default(true)->after('is_active');
            $table->boolean('deposit_enabled')->default(true)->after('wallet_enabled');
            $table->boolean('marketplace_enabled')->default(true)->after('deposit_enabled');
            $table->boolean('reporting_currency_enabled')->default(false)->after('marketplace_enabled');
            $table->text('admin_notes')->nullable()->after('reporting_currency_enabled');
            $table->foreignId('updated_by')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'thousands_separator', 'decimal_separator', 'wallet_enabled', 'deposit_enabled',
                'marketplace_enabled', 'reporting_currency_enabled', 'admin_notes',
            ]);
        });
    }
};
