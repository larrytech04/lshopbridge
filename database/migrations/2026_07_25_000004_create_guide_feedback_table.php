<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Immutable event log of "was this helpful?" votes — never edited, only appended. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('was_helpful');
            $table->string('reason')->nullable(); // outdated|unclear|missing_steps|broken_link|outdated_screenshot|other
            $table->text('comment')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_feedback');
    }
};
