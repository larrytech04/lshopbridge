<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addCommon = function (Blueprint $table) {
            $table->string('internal_code')->nullable();
            $table->string('purpose')->default('collection'); // collection|settlement|escrow|other
            $table->decimal('min_deposit', 16, 2)->nullable();
            $table->decimal('max_deposit', 16, 2)->nullable();
            $table->string('confirmation_method')->nullable(); // manual_review|auto_reference_match
            $table->boolean('auto_reconciliation')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->text('admin_notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        };

        Schema::table('momo_numbers', function (Blueprint $table) use ($addCommon) {
            $addCommon($table);
        });
        Schema::table('crypto_wallets', function (Blueprint $table) use ($addCommon) {
            $addCommon($table);
            $table->foreignId('country_id')->nullable()->after('network')->constrained()->nullOnDelete();
        });
        Schema::table('bank_accounts', function (Blueprint $table) use ($addCommon) {
            $addCommon($table);
            $table->string('iban')->nullable()->after('account_number');
            $table->string('routing_number')->nullable()->after('swift');
        });
    }

    public function down(): void
    {
        $dropCommon = function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'internal_code', 'purpose', 'min_deposit', 'max_deposit', 'confirmation_method',
                'auto_reconciliation', 'sort', 'admin_notes',
            ]);
            $table->dropSoftDeletes();
        };

        Schema::table('momo_numbers', function (Blueprint $table) use ($dropCommon) {
            $dropCommon($table);
        });
        Schema::table('crypto_wallets', function (Blueprint $table) use ($dropCommon) {
            $dropCommon($table);
            $table->dropConstrainedForeignId('country_id');
        });
        Schema::table('bank_accounts', function (Blueprint $table) use ($dropCommon) {
            $dropCommon($table);
            $table->dropColumn(['iban', 'routing_number']);
        });
    }
};
