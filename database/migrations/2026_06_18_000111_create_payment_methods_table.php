<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What the user sees on the deposit screen. Fully admin-managed.
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();        // mtn_momo, orange_money, bank_transfer, crypto, card
            $table->string('name');
            $table->string('type');                  // momo | bank | crypto | card
            $table->string('provider_code')->nullable(); // links to a payment_providers.code for automation
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();    // shown for manual flows
            $table->string('logo_path')->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('min_amount', 14, 2)->default(0);
            $table->decimal('max_amount', 14, 2)->nullable();
            $table->boolean('is_automated')->default(false); // true => API charge + webhook
            $table->boolean('requires_proof')->default(true); // manual flows need proof upload
            $table->boolean('is_active')->default(true);
            $table->json('countries')->nullable();       // restrict to ISO2 list, null = all
            $table->json('fields')->nullable();          // extra fields to collect (phone, network...)
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
