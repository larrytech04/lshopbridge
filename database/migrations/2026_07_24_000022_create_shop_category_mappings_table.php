<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_source_id')->constrained()->cascadeOnDelete();
            $table->string('external_category');
            $table->foreignId('shop_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('suggested'); // suggested|confirmed
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['import_source_id', 'external_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_category_mappings');
    }
};
