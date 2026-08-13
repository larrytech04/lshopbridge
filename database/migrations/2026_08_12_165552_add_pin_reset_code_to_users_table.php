<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-service "forgot PIN" flow (see PinResetService): password +
     * emailed code, so a user who can't remember their transaction PIN
     * never needs server/tinker access to clear it. Stored hashed, same as
     * the PIN itself and the reauth code, this is a credential, not a
     * display value.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_reset_code')->nullable()->after('reauth_code_sent_at');
            $table->timestamp('pin_reset_code_expires_at')->nullable()->after('pin_reset_code');
            $table->timestamp('pin_reset_code_sent_at')->nullable()->after('pin_reset_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pin_reset_code', 'pin_reset_code_expires_at', 'pin_reset_code_sent_at']);
        });
    }
};
