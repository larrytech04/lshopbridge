<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->string('occupation')->nullable()->after('address');
            $table->string('source_of_funds')->nullable()->after('occupation');
            $table->boolean('is_pep')->default(false)->after('source_of_funds');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropColumn(['occupation', 'source_of_funds', 'is_pep']);
        });
    }
};
