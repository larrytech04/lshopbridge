<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idle-session re-authentication: after 15+ minutes away, the next request
     * is challenged for the transaction PIN (if set) and this emailed code
     * before the session is usable again. Stored hashed, same as the PIN
     * itself — this is a credential, not a display value.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('reauth_code')->nullable()->after('transaction_pin_set_at');
            $table->timestamp('reauth_code_expires_at')->nullable()->after('reauth_code');
            $table->timestamp('reauth_code_sent_at')->nullable()->after('reauth_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reauth_code', 'reauth_code_expires_at', 'reauth_code_sent_at']);
        });
    }
};
