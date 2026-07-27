<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real, enforced targeting for banners — evaluated by BannerAdminService::visibleTo()
 * wherever a banner is rendered (home hero, sitewide announce bar). No admin-facing
 * targeting field exists here that isn't actually checked at render time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('audience')->default('everyone')->after('position'); // everyone|guest|logged_in|verified|agent
            $table->foreignId('country_id')->nullable()->after('audience')->constrained()->nullOnDelete();
            $table->timestamp('starts_at')->nullable()->after('country_id');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->foreignId('updated_by')->nullable()->after('sort')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['audience', 'starts_at', 'ends_at']);
            $table->dropSoftDeletes();
        });
    }
};
