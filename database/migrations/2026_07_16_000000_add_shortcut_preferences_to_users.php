<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('shortcuts_enabled')->default(true)->after('locale');
            $table->json('shortcut_overrides')->nullable()->after('shortcuts_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shortcuts_enabled', 'shortcut_overrides']);
        });
    }
};
