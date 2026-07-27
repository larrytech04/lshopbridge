<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_id')->constrained('fees')->cascadeOnDelete();
            $table->string('new_type')->nullable();
            $table->decimal('new_value', 12, 4)->nullable();
            $table->decimal('new_min_fee', 14, 2)->nullable();
            $table->decimal('new_max_fee', 14, 2)->nullable();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->string('status')->default('scheduled'); // scheduled|applied|expired|cancelled
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_schedules');
    }
};
