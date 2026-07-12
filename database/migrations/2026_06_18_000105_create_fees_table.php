<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('applies_to')->default('funding'); // deposit | funding | all
            $table->string('scope')->nullable();              // optional: payment method code / app type
            $table->string('type')->default('percent');       // percent | fixed
            $table->decimal('value', 12, 4)->default(0);       // % or fixed amount
            $table->decimal('min_fee', 14, 2)->default(0);
            $table->decimal('max_fee', 14, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
