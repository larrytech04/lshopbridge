<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
            $table->string('banner_path')->nullable()->after('image_path');
            $table->string('product_type')->nullable()->after('banner_path'); // hint only — filters product-type choices when adding a product here
            $table->boolean('featured')->default(false)->after('is_active');
            $table->boolean('menu_visible')->default(true)->after('featured');
            $table->foreignId('default_fee_id')->nullable()->after('menu_visible')->constrained('fees')->nullOnDelete();
            $table->json('restricted_countries')->nullable()->after('default_fee_id'); // ISO2 list, null = no restriction
            $table->string('seo_title')->nullable()->after('restricted_countries');
            $table->text('meta_description')->nullable()->after('seo_title');
            $table->string('canonical_url')->nullable()->after('meta_description');
            $table->text('notes')->nullable()->after('canonical_url'); // internal, never shown on storefront
        });
    }

    public function down(): void
    {
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_fee_id');
            $table->dropColumn([
                'image_path', 'banner_path', 'product_type', 'featured', 'menu_visible',
                'restricted_countries', 'seo_title', 'meta_description', 'canonical_url', 'notes',
            ]);
        });
    }
};
