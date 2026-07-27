<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hash-chains the audit log: each row's `hash` covers its own fields plus the
 * previous row's hash, so any row edited or deleted after the fact breaks the
 * chain from that point forward. This is application-level tamper *evidence*
 * (detects a modified table), not tamper *prevention* — a database superuser
 * could still rewrite the whole chain. Real prevention needs off-server log
 * shipping (see the infrastructure checklist), which is outside this app.
 * Existing rows predate this column and are left with a null hash; the
 * verification routine treats the chain as starting fresh from the first
 * hashed row rather than flagging pre-existing history as "tampered".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('hash', 64)->nullable()->after('user_agent');
            $table->string('prev_hash', 64)->nullable()->after('hash');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['hash', 'prev_hash']);
        });
    }
};
