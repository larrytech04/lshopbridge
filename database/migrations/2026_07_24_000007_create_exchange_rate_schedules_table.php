<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Future rate changes. No queue/cron infrastructure exists in this codebase
        // (confirmed: no app/Jobs directory), so schedules are applied on read —
        // RateService checks for a due schedule before falling back to the static
        // exchange_rates row, rather than requiring a background job to "promote" it.
        Schema::create('exchange_rate_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 20, 8);
            $table->decimal('margin_percent', 8, 4)->default(0);
            $table->string('margin_type')->default('percentage');
            $table->decimal('margin_fixed', 20, 8)->nullable();
            $table->decimal('custom_effective_rate', 20, 8)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status')->default('scheduled'); // scheduled | applied | expired | cancelled
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['base_currency', 'quote_currency', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_schedules');
    }
};
