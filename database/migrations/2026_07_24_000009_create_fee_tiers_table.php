<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_id')->constrained('fees')->cascadeOnDelete();
            $table->decimal('min_amount', 16, 2);
            $table->decimal('max_amount', 16, 2)->nullable(); // null = open-ended (above min_amount)
            $table->decimal('percent', 8, 4)->default(0);
            $table->decimal('fixed', 14, 2)->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_tiers');
    }
};
