<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level')->unique();   // 0,1,2,3
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('requirements')->nullable();          // list shown to user
            $table->decimal('daily_limit', 16, 2)->default(0);
            $table->decimal('monthly_limit', 16, 2)->default(0);
            $table->decimal('per_transaction_limit', 16, 2)->default(0);
            $table->string('currency', 3)->default('XAF');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_levels');
    }
};
