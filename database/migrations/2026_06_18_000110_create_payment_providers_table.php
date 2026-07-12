<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-controllable provider registry. Secrets stay in env/config —
        // this table only stores the toggle, mode and non-sensitive metadata.
        Schema::create('payment_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();        // mtn_momo, orange_money, flutterwave, crypto, card, alipay
            $table->string('name');
            $table->string('kind')->default('collection'); // collection | funding
            $table->string('mode')->default('sandbox');    // sandbox | live
            $table->boolean('is_active')->default(true);
            $table->json('supports')->nullable();    // ['momo','card','crypto']
            $table->json('meta')->nullable();        // non-secret config notes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_providers');
    }
};
