<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('excerpt');
            $table->string('meta_description', 300)->nullable()->after('meta_title');
            $table->unsignedInteger('version')->default(1)->after('last_reviewed_at');
            $table->foreignId('updated_by')->nullable()->after('version')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['meta_title', 'meta_description', 'version']);
            $table->dropSoftDeletes();
        });
    }
};
