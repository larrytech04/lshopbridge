<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // Categorizes what kind of agent this is. Every agent in this platform is
            // currently a shipping/sourcing operator, so existing rows default to
            // 'shipping_agent' below rather than an arbitrary/fabricated category.
            $table->string('agent_type')->default('shipping_agent')->after('business_name');

            // Featured-placement controls beyond the existing plain is_featured flag.
            $table->date('featured_from')->nullable()->after('is_featured');
            $table->date('featured_until')->nullable()->after('featured_from');
            $table->unsignedInteger('featured_priority')->default(0)->after('featured_until');
            $table->string('featured_label')->nullable()->after('featured_priority');

            // Private, staff-only notes — never shown to the agent (mirrors users.admin_notes).
            $table->text('admin_notes')->nullable()->after('rejection_reason');

            $table->softDeletes();

            $table->index('agent_type');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex(['agent_type']);
            $table->dropSoftDeletes();
            $table->dropColumn(['agent_type', 'featured_from', 'featured_until', 'featured_priority', 'featured_label', 'admin_notes']);
        });
    }
};
