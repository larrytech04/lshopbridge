<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links each transaction to the fee rule that priced it, and freezes a JSON
 * snapshot of that rule's name/type/rate/fixed/currency at the moment of
 * charge. fee_id is nullOnDelete (never cascades) and the snapshot is never
 * rewritten, so editing or archiving a fee afterward cannot alter what a
 * completed transaction is recorded as having charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->foreignId('fee_id')->nullable()->after('fee')->constrained('fees')->nullOnDelete();
            $table->json('fee_snapshot')->nullable()->after('fee_id');
        });

        Schema::table('funding_requests', function (Blueprint $table) {
            $table->foreignId('fee_id')->nullable()->after('fee')->constrained('fees')->nullOnDelete();
            $table->json('fee_snapshot')->nullable()->after('fee_id');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_id');
            $table->dropColumn('fee_snapshot');
        });

        Schema::table('funding_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_id');
            $table->dropColumn('fee_snapshot');
        });
    }
};
