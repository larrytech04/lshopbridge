<?php

use App\Models\Country;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Gives country pages a real URL (/countries/cameroon) instead of an
     * ISO2 code — matches the slug convention every other content model in
     * this app already uses (Page, Guide, ShopProduct, ...).
     */
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        Country::whereNull('slug')->orderBy('id')->each(function (Country $country) {
            $base = Str::slug($country->name) ?: Str::lower($country->iso2);
            $slug = $base;
            $suffix = 2;

            while (Country::where('slug', $slug)->where('id', '!=', $country->id)->exists()) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $country->update(['slug' => $slug]);
        });

        Schema::table('countries', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
