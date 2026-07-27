<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            // draft|awaiting_quotes|quote_received|accepted|awaiting_pickup|in_transit|delivered|cancelled|disputed

            $table->string('origin_country', 2);
            $table->string('origin_city');
            $table->text('origin_address')->nullable();
            $table->string('destination_country', 2);
            $table->string('destination_city');
            $table->text('destination_address')->nullable();

            $table->string('package_description');
            $table->decimal('package_weight_kg', 8, 2)->nullable();
            $table->decimal('package_value', 14, 2)->nullable();
            $table->string('package_currency', 3)->default('XAF');
            $table->json('documents')->nullable(); // private-disk paths

            // FK to shipping_quotes added in the next migration, once that table exists (avoids a circular create-order).
            $table->unsignedBigInteger('accepted_quote_id')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_requests');
    }
};
