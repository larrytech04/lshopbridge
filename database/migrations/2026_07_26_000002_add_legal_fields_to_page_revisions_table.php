<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_revisions', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
            $table->longText('plain_summary')->nullable()->after('excerpt');
            $table->json('applicable_services')->nullable()->after('plain_summary');
            $table->json('applicable_countries')->nullable()->after('applicable_services');
            $table->date('effective_date')->nullable()->after('applicable_countries');
        });
    }

    public function down(): void
    {
        Schema::table('page_revisions', function (Blueprint $table) {
            $table->dropColumn(['category', 'plain_summary', 'applicable_services', 'applicable_countries', 'effective_date']);
        });
    }
};
