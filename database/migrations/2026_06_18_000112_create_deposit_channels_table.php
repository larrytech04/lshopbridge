<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MoMo numbers the platform collects to (manual flow display).
        Schema::create('momo_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('provider');              // mtn | orange
            $table->string('number');
            $table->string('account_name');
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Crypto wallet addresses the platform collects to.
        Schema::create('crypto_wallets', function (Blueprint $table) {
            $table->id();
            $table->string('asset');                 // USDT, BTC, ETH
            $table->string('network');               // TRC20, ERC20, BTC
            $table->string('address');
            $table->string('memo')->nullable();
            $table->string('qr_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Bank accounts for bank transfer deposits.
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('branch')->nullable();
            $table->string('swift')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('crypto_wallets');
        Schema::dropIfExists('momo_numbers');
    }
};
