<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            // Case queue management.
            $table->string('priority')->nullable()->after('target_level'); // low | medium | high | critical
            $table->foreignId('assigned_to')->nullable()->after('priority')->constrained('users')->nullOnDelete();
            $table->foreignId('locked_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by');

            // Recorded by the reviewer while inspecting the physical/scanned document (not user-submitted).
            $table->date('document_expiry_date')->nullable()->after('proof_of_address_path');

            // Manual review outcomes: document authenticity, face/liveness comparison,
            // address verification, AML/PEP/sanctions screening. No automated provider is
            // connected for any of these — every sub-object is reviewer-entered and carries
            // its own status/checked_by/checked_at/notes so the workspace can render an
            // honest "not connected, manual review only" state instead of fabricating results.
            $table->json('review_checks')->nullable()->after('is_pep');

            $table->index(['status', 'priority']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropIndex(['status', 'priority']);
            $table->dropIndex(['assigned_to']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn(['priority', 'locked_at', 'document_expiry_date', 'review_checks']);
        });
    }
};
