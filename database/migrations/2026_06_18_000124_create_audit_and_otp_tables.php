<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin + transaction audit trail.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // actor
            $table->string('action');                 // e.g. deposit.confirmed
            $table->string('description')->nullable();
            $table->nullableMorphs('auditable');      // affected model
            $table->json('properties')->nullable();   // before/after, context
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });

        // Phone/email OTP codes (verification + sensitive actions).
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel')->default('sms'); // sms | email
            $table->string('destination');             // phone or email
            $table->string('purpose')->default('phone_verification');
            $table->string('code_hash');               // hashed, never plaintext
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('audit_logs');
    }
};
