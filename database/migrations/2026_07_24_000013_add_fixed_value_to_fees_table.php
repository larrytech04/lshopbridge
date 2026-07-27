<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separate from `value`, which keeps its original meaning per type
     * (the percent for "percent", the flat amount for "fixed") so existing
     * rows and the old form keep working unchanged. `fixed_value` only comes
     * into play for the new "fixed_plus_percent" type, as the flat component
     * added to `value`'s percentage component.
     */
    public function up(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->decimal('fixed_value', 14, 2)->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('fixed_value');
        });
    }
};
