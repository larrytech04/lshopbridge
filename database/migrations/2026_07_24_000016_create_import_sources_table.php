<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every row here is one "connector slot" — native/manual, file-based (CSV/XLSX/
 * JSON/XML), a store platform (Shopify, WooCommerce...), a marketplace, a
 * dropshipping/POD supplier, a China-sourcing partner, a digital-service
 * provider, or a generic supplier feed. Credentials are encrypted and only a
 * handful of file-based/native sources are ever usable without them — the
 * rest sit at status=not_connected until an admin configures real API access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // native, csv, xlsx, json, xml, shopify, woocommerce, cj, printful, ...
            $table->string('name');
            $table->string('group'); // native | file | store_platform | marketplace | dropship | china_sourcing | digital_service | generic_supplier
            $table->string('connector_class')->nullable(); // FQCN implementing ProductSourceConnector, when one exists
            $table->text('credentials')->nullable(); // encrypted:array — API keys/tokens, never plaintext
            $table->string('status')->default('not_connected');
            $table->string('auto_sync')->default('manual'); // manual|hourly|every_few_hours|daily|weekly|webhook|disabled
            $table->timestamp('last_import_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_sources');
    }
};
