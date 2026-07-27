<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipping_requests', function (Blueprint $table) {
            // Read receipt for the customer's own view of the request — used to
            // detect "something changed since you last looked" for the sidebar badge.
            $table->timestamp('customer_viewed_at')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->dropColumn('customer_viewed_at');
        });
    }
};
