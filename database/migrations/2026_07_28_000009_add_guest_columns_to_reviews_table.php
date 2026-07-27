<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a guest feedback path alongside the existing verified-customer review
 * flow. Guest rows always have status='pending' and is_guest=true so they
 * can never be confused with a verified purchaser's review in moderation —
 * the review-anonymity trust concern this schema exists to prevent. A null
 * user_id has nothing to cascade from, so the existing cascadeOnDelete on
 * user_id is unaffected for guest rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->boolean('is_guest')->default(false)->after('user_id');
            $table->string('guest_name')->nullable()->after('is_guest');
            $table->string('guest_email')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['is_guest', 'guest_name', 'guest_email']);
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
