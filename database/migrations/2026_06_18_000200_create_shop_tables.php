<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-managed catalogue categories (the filter pills on the storefront).
        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('icon')->default('sparkles'); // icon component name
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('accent')->default('brand');   // colour accent for cards
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_category_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('brand')->nullable();
            // Delivery behaviour: giftcard | esim | vpn | data | gaming | streaming | software | other
            $table->string('type')->default('giftcard');
            $table->string('region')->nullable();          // for eSIM / data (country/region)
            $table->string('summary')->nullable();
            $table->text('description')->nullable();
            $table->text('redeem_instructions')->nullable();
            $table->string('image_path')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_best_deal')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        // Each product has one or more purchasable variants (plans/denominations).
        Schema::create('shop_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_product_id')->constrained()->cascadeOnDelete();
            $table->string('name');                        // "5GB / 30 days", "$25", "1 Month"
            $table->string('sku')->nullable();
            $table->decimal('price', 14, 2);
            $table->decimal('compare_at_price', 14, 2)->nullable(); // strike-through
            $table->string('currency', 3)->default('XAF');
            $table->string('data_amount')->nullable();     // eSIM/data
            $table->unsignedInteger('validity_days')->nullable();
            $table->decimal('denomination', 14, 2)->nullable(); // gift card face value
            $table->integer('stock')->nullable();          // null = unlimited / auto-generated
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Pre-loaded deliverable secrets (codes/credentials) — optional inventory.
        Schema::create('shop_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_variant_id')->constrained()->cascadeOnDelete();
            $table->text('secret');                        // the code / credential
            $table->boolean('is_used')->default(false);
            $table->foreignId('shop_order_item_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['shop_variant_id', 'is_used']);
        });

        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();         // PB-SHP-XXXX
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // pending | paid | fulfilled | failed | refunded
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('fee', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('currency', 3)->default('XAF');
            $table->string('payment_source')->default('wallet'); // wallet | direct
            $table->string('email')->nullable();           // delivery email
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shop_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                        // snapshot
            $table->string('type')->default('giftcard');
            $table->decimal('unit_price', 14, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('line_total', 14, 2);
            $table->json('delivered')->nullable();         // array of delivered secrets
            $table->string('status')->default('pending');  // pending | fulfilled
            $table->timestamps();
        });

        // Link a payment intent to a shop order for webhook settlement.
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->foreignId('shop_order_id')->nullable()->after('funding_request_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shop_order_id');
        });
        Schema::dropIfExists('shop_order_items');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_codes');
        Schema::dropIfExists('shop_variants');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('shop_categories');
    }
};
