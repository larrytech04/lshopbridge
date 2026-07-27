<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nullable and only ever populated when a geo-IP provider is actually
 * configured (see GeoIpService) — stays null otherwise rather than
 * fabricating a location for logins made before/without that setup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_attempts', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('ip');
            $table->boolean('was_new_country')->nullable()->after('was_new_device');
        });
    }

    public function down(): void
    {
        Schema::table('login_attempts', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
