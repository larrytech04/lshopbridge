<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->string('difficulty')->default('beginner')->after('category'); // beginner|intermediate|advanced
            $table->string('meta_title')->nullable()->after('cta_url');
            $table->string('meta_description', 300)->nullable()->after('meta_title');
            $table->foreignId('updated_by')->nullable()->after('sort')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['difficulty', 'meta_title', 'meta_description']);
            $table->dropSoftDeletes();
        });
    }
};
