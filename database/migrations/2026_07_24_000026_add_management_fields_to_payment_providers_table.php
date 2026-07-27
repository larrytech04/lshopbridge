<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_providers', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
            $table->text('description')->nullable()->after('logo_path');
            $table->json('countries')->nullable()->after('supports'); // ISO2 list, null = all
            $table->json('currencies')->nullable()->after('countries');
            $table->unsignedInteger('priority')->default(0)->after('currencies');
            $table->timestamp('last_tested_at')->nullable()->after('priority');
            $table->boolean('last_test_ok')->nullable()->after('last_tested_at');
            $table->string('last_test_message')->nullable()->after('last_test_ok');
            $table->foreignId('updated_by')->nullable()->after('last_test_message')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('payment_providers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn([
                'logo_path', 'description', 'countries', 'currencies', 'priority',
                'last_tested_at', 'last_test_ok', 'last_test_message',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
