<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configurable reason templates so reviewers pick a consistent internal reason and
        // (optionally) a separate, sanitized customer-facing message. Internal fraud/AML
        // reasoning must never leak into the customer_message field.
        Schema::create('kyc_decision_templates', function (Blueprint $table) {
            $table->id();
            $table->string('decision_type');
            $table->string('name');
            $table->text('internal_reason')->nullable();
            $table->text('customer_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['decision_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_decision_templates');
    }
};
