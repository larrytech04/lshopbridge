<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real fields backing real MFA (the existing two_factor_enabled/two_factor_secret
 * columns already existed but nothing in the login path ever checked them — this
 * migration adds what's needed to make MFA actually functional, not just cosmetic).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('two_factor_disabled_at')->nullable()->after('two_factor_recovery_codes');
            $table->timestamp('password_changed_at')->nullable()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_confirmed_at', 'two_factor_recovery_codes', 'two_factor_disabled_at', 'password_changed_at']);
        });
    }
};
