<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the hardcoded step arrays previously baked into
 * public/how-it-works.blade.php ($fundSteps, $shopSteps, $promises). The
 * <x-journey> component already renders any number of steps dynamically, so
 * these become real, admin-editable, reorderable rows with zero template risk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_steps', function (Blueprint $table) {
            $table->id();
            $table->string('group'); // fund_step|shop_step|promise
            $table->string('icon'); // asset filename (fund_step/shop_step) or an <x-icon> name (promise)
            $table->string('title');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_steps');
    }
};
