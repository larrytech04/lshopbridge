<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable log of every login attempt (success and failure), the factual
 * basis for new-device detection and login-security review. No IP-geolocation
 * (country/city) is recorded — no geolocation provider is configured in this
 * app, and fabricating approximate location from nothing would be dishonest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->boolean('successful');
            $table->boolean('was_new_device')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
