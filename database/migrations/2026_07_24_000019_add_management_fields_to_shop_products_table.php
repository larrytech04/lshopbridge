<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            // draft | active | disabled | archived — "scheduled" is computed from scheduled_publish_at, never stored.
            $table->string('status')->default('active')->after('is_active');
            $table->timestamp('scheduled_publish_at')->nullable()->after('status');
            $table->string('source')->default('native')->after('scheduled_publish_at'); // native|manual|csv|xlsx|json|xml|<connector code>
            $table->foreignId('supplier_id')->nullable()->after('source')->constrained('suppliers')->nullOnDelete();
            $table->foreignId('import_source_id')->nullable()->after('supplier_id')->constrained('import_sources')->nullOnDelete();
            $table->foreignId('product_import_id')->nullable()->after('import_source_id')->constrained('product_imports')->nullOnDelete();
            $table->string('external_product_id')->nullable()->after('product_import_id');
            $table->string('provider_status')->nullable()->after('external_product_id'); // null = not provider-synced
            $table->timestamp('last_synced_at')->nullable()->after('provider_status');
            $table->text('admin_notes')->nullable()->after('last_synced_at');
            $table->foreignId('updated_by')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index('status');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('import_source_id');
            $table->dropConstrainedForeignId('product_import_id');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'status', 'scheduled_publish_at', 'source', 'external_product_id',
                'provider_status', 'last_synced_at', 'admin_notes',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
