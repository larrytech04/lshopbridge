<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_id')->nullable()->constrained('fees')->nullOnDelete();
            $table->string('name');
            $table->string('applies_to');
            $table->string('type');
            $table->decimal('value', 12, 4)->default(0);
            $table->decimal('min_fee', 14, 2)->default(0);
            $table->decimal('max_fee', 14, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('is_active');
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->string('event'); // created|updated|activated|deactivated|archived|schedule_applied
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_history');
    }
};
