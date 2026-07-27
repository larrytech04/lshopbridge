<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configures the wallet types this platform already supports (its `code`
 * matches App\Enums\AppType's backing values: alipay, wechat, other). This
 * table controls LIMITS/instructions/provider linkage for those types — it
 * does not let an admin invent a genuinely new payment rail, since delivery
 * (BeneficiaryController, ShopService::generateSecret) is still keyed off
 * the fixed AppType enum. Adding a real new wallet type needs a matching
 * enum case and delivery-logic support, not just a new row here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('china_wallet_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('account_identifier_type')->default('custom'); // email|phone|wallet_id|qr_code|card_number|username|custom
            $table->boolean('qr_required')->default(false);
            $table->boolean('account_name_required')->default(true);
            $table->boolean('phone_required')->default(false);
            $table->json('country_restrictions')->nullable(); // ISO2 list, null = no restriction
            $table->unsignedTinyInteger('min_kyc_level')->nullable();
            $table->decimal('min_funding_amount', 16, 2)->nullable();
            $table->decimal('max_funding_amount', 16, 2)->nullable();
            $table->decimal('daily_limit', 16, 2)->nullable();
            $table->decimal('monthly_limit', 16, 2)->nullable();
            $table->boolean('automated_funding')->default(false);
            $table->boolean('manual_funding')->default(true);
            $table->string('provider_code')->nullable(); // loose reference to payment_providers.code, like PaymentMethod.provider_code
            $table->string('processing_time_estimate')->nullable();
            $table->text('customer_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->text('admin_notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('china_wallet_types');
    }
};
