<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('name');
            $table->text('description')->nullable()->after('code');
            $table->string('fee_payer')->default('customer')->after('type');
            $table->decimal('provider_markup_percent', 8, 4)->nullable()->after('value');
            $table->decimal('min_amount', 16, 2)->nullable()->after('max_fee'); // min transaction amount this rule applies to
            $table->decimal('max_amount', 16, 2)->nullable()->after('min_amount'); // max transaction amount this rule applies to
            $table->string('country', 2)->nullable()->after('currency');
            $table->string('region')->nullable()->after('country');
            $table->string('customer_role')->nullable()->after('region');
            $table->unsignedTinyInteger('kyc_level')->nullable()->after('customer_role');
            $table->string('payment_provider')->nullable()->after('kyc_level');
            $table->string('china_wallet_type')->nullable()->after('payment_provider');
            $table->boolean('taxable')->default(false)->after('is_active');
            $table->boolean('under_review')->default(false)->after('taxable');
            $table->date('effective_start_date')->nullable()->after('under_review');
            $table->date('effective_end_date')->nullable()->after('effective_start_date');
            $table->text('notes')->nullable()->after('effective_end_date');
            $table->foreignId('updated_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'code', 'description', 'fee_payer', 'provider_markup_percent', 'min_amount', 'max_amount',
                'country', 'region', 'customer_role', 'kyc_level', 'payment_provider', 'china_wallet_type',
                'taxable', 'under_review', 'effective_start_date', 'effective_end_date', 'notes',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
