<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_accounts', function (Blueprint $table) {
            // Private, staff-only notes — never shown to the customer.
            $table->text('admin_notes')->nullable()->after('rejection_reason');
            $table->string('rejection_category')->nullable()->after('rejection_reason');
            $table->boolean('resubmission_allowed')->default(true)->after('rejection_category');

            // Manual verification checklist (identity/name-match/QR readability/etc). No
            // automated identity, OCR, or fingerprint-matching provider is connected —
            // every item is reviewer-entered, same convention as the KYC workspace.
            $table->json('review_checklist')->nullable()->after('meta');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_accounts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['admin_notes', 'rejection_category', 'resubmission_allowed', 'review_checklist']);
        });
    }
};
