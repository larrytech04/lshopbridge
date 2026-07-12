<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-configurable risk rules consumed by App\Services\Risk\RiskEngine.
        Schema::create('risk_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();   // name_mismatch, velocity, large_tx, blocked_country, failed_attempts, new_device
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('action')->default('review'); // flag | review | block
            $table->string('severity')->default('medium'); // low | medium | high
            $table->json('params')->nullable();  // thresholds
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Raised when a rule trips. Drives admin manual-review queue.
        Schema::create('risk_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_code');
            $table->string('severity')->default('medium');
            $table->string('reason');
            $table->nullableMorphs('flaggable');  // Deposit / FundingRequest / PaymentIntent
            $table->string('status')->default('open'); // open | reviewed | dismissed
            $table->json('context')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_flags');
        Schema::dropIfExists('risk_rules');
    }
};
