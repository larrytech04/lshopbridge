<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Everything else the Marketplace mega-menu needs already exists on this
 * table (icon, tagline, description, sort, featured, menu_visible,
 * restricted_countries, parent_id hierarchy) — this only adds the pieces
 * that were genuinely missing: an admin-controlled badge, and an
 * availability window for "coming soon" / time-limited categories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->string('navigation_badge')->nullable()->after('featured'); // New | Popular | Sale | Limited | Coming Soon | null
            $table->string('navigation_badge_style')->nullable()->after('navigation_badge'); // brand | emerald | amber | rose | slate
            $table->timestamp('available_from')->nullable()->after('navigation_badge_style');
            $table->timestamp('available_until')->nullable()->after('available_from');
        });
    }

    public function down(): void
    {
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->dropColumn(['navigation_badge', 'navigation_badge_style', 'available_from', 'available_until']);
        });
    }
};
