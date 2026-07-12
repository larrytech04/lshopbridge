<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso2', 2)->unique();
            $table->string('iso3', 3)->nullable();
            $table->string('dial_code', 8)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->string('flag_emoji', 8)->nullable();
            $table->boolean('is_active')->default(true);   // available for signup/use
            $table->boolean('is_blocked')->default(false); // risk: blocked country
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
