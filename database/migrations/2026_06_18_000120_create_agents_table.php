<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->string('registration_number')->nullable();
            $table->text('bio')->nullable();
            $table->string('logo_path')->nullable();       // public disk (branding)

            // Verification docs — PRIVATE disk
            $table->string('business_doc_path')->nullable();
            $table->string('id_doc_path')->nullable();

            // Warehouse / contact
            $table->string('warehouse_address')->nullable();
            $table->string('warehouse_city')->nullable();
            $table->foreignId('warehouse_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('wechat')->nullable();
            $table->json('cities')->nullable();            // served cities
            $table->json('shipping_methods')->nullable();  // ['air','sea','express']

            $table->string('status')->default('pending');  // pending | approved | rejected | suspended
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            // Reputation
            $table->decimal('rating', 4, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('completed_orders')->default(0);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->index('status');
        });

        Schema::create('agent_country', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->unique(['agent_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_country');
        Schema::dropIfExists('agents');
    }
};
