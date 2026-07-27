<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short-lived restrictions applied after confirmed high-confidence abuse.
 * Scoped by hashed IP or content fingerprint, never a raw identifier.
 * `form_type` null means the restriction applies to all protected forms.
 * Expired rows are pruned by the CleanExpiredFormSecurityData command, not
 * deleted immediately, so recent restriction history stays inspectable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_form_restrictions', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');  // ip | fingerprint
            $table->string('subject_value', 64); // hash
            $table->string('form_type')->nullable();
            $table->string('reason');
            $table->timestamp('expires_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['subject_type', 'subject_value']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_form_restrictions');
    }
};
