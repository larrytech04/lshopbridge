<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('method');                 // air | sea | express
            $table->string('origin')->default('China');
            $table->foreignId('destination_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->decimal('price_per_kg', 12, 2)->nullable();
            $table->decimal('price_per_cbm', 12, 2)->nullable();
            $table->decimal('min_charge', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedSmallInteger('estimated_days_min')->nullable();
            $table->unsignedSmallInteger('estimated_days_max')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');    // 1..5
            $table->text('comment')->nullable();
            $table->string('order_reference')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
        });

        // A user requesting assistance / quote from an agent (in-platform lead).
        Schema::create('agent_leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('shipping_method')->nullable();
            $table->text('message');
            $table->string('status')->default('new'); // new | contacted | in_progress | completed | closed
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_leads');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('shipping_rates');
    }
};
