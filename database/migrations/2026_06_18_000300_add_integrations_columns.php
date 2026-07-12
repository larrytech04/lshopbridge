<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_providers', function (Blueprint $table) {
            // Encrypted JSON of admin-entered API credentials (overrides .env).
            $table->text('credentials')->nullable()->after('meta');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('referred_by')->index();
            $table->string('avatar_url')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('payment_providers', function (Blueprint $table) {
            $table->dropColumn('credentials');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar_url']);
        });
    }
};
