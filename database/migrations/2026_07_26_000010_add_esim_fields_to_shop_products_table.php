<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            // Only meaningful for type=esim: local (one destination), regional
            // (several countries in one region), or global (multi-region /
            // worldwide). Existing `region` column already holds the display
            // name ("China", "Global", "United States") — this is the
            // structural scope used for filtering/routing on /esim.
            $table->string('esim_scope')->nullable()->after('region');

            // Real ISO2 codes actually covered by this destination/plan group
            // — for a local plan this is just the one country, for
            // regional/global it's the real coverage list synced from (or,
            // until then, manually entered against) the provider. Never
            // derived from a hardcoded country count.
            $table->json('esim_coverage_countries')->nullable()->after('esim_scope');
        });
    }

    public function down(): void
    {
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropColumn(['esim_scope', 'esim_coverage_countries']);
        });
    }
};
