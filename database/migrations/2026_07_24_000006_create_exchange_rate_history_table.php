<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable snapshot log. Never edited or deleted. The live `exchange_rates`
        // row stays a single current-value-per-pair table (so RateService's lookup
        // query is untouched) — this table is the append-only history alongside it.
        Schema::create('exchange_rate_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates')->nullOnDelete();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 20, 8);
            $table->decimal('margin_percent', 8, 4)->default(0);
            $table->string('margin_type')->default('percentage');
            $table->decimal('margin_fixed', 20, 8)->nullable();
            $table->decimal('custom_effective_rate', 20, 8)->nullable();
            $table->decimal('effective_rate', 20, 8);
            $table->boolean('is_active');
            $table->string('event'); // created | updated | activated | deactivated | archived | schedule_applied
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['base_currency', 'quote_currency', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_history');
    }
};
