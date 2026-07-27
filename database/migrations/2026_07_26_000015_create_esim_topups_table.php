<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esim_provisioning_id')->constrained()->cascadeOnDelete();
            $table->string('provider_topup_package_id')->nullable();
            $table->string('name');
            $table->unsignedBigInteger('data_mb')->nullable();
            $table->boolean('is_unlimited_data')->default(false);
            $table->unsignedInteger('validity_days')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency', 8);
            $table->string('status')->default('pending'); // pending | processing | completed | failed | refunded
            $table->string('provider_order_id')->nullable();
            $table->string('idempotency_key')->unique();
            $table->foreignId('shop_order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('provider_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_topups');
    }
};
