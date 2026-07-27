<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "launch_status" is the richer, admin-facing lifecycle (active/coming_soon/
 * restricted/maintenance/disabled); is_active/is_blocked stay in sync so
 * every existing query (RiskEngine::blockedCountry(), Country::scopeActive())
 * keeps working unchanged. No per-country service toggles (deposit/funding/
 * marketplace enabled) are added here — nothing in the codebase enforces
 * country-scoped feature availability yet, so a toggle with no effect would
 * be misleading. That belongs with the (deferred) Feature Availability page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('launch_status')->default('active')->after('is_blocked');
            $table->text('admin_notes')->nullable()->after('launch_status');
            $table->foreignId('updated_by')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['launch_status', 'admin_notes']);
        });
    }
};
