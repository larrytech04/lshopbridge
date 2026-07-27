<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_variants', function (Blueprint $table) {
            // data_amount/validity_days already exist. These are the
            // remaining plan facts a customer needs to compare eSIM plans
            // honestly — every one nullable, since only real provider/admin
            // data should ever populate them (never inferred/guessed).
            $table->boolean('is_unlimited_data')->default(false)->after('data_amount');
            $table->json('network_speeds')->nullable()->after('validity_days'); // e.g. ["4G","5G"]
            $table->json('networks')->nullable()->after('network_speeds'); // real operator names, from provider/admin
            $table->boolean('hotspot_supported')->nullable()->after('networks');
            $table->boolean('voice_supported')->nullable()->after('hotspot_supported');
            $table->boolean('sms_supported')->nullable()->after('voice_supported');
            $table->boolean('topup_supported')->default(false)->after('sms_supported');

            // "first_connect" | "on_install" | "on_date" | "manual" | "provider_defined"
            // — never a single hardcoded activation message for every plan.
            $table->string('activation_policy')->nullable()->after('topup_supported');
            $table->unsignedInteger('installation_deadline_days')->nullable()->after('activation_policy');
            $table->text('fair_usage_note')->nullable()->after('installation_deadline_days');

            // external_id/provider_status already exist generically (added
            // 2026_07_24) and are reused as the provider package id / sync
            // status — no esim-specific duplicates needed.
        });
    }

    public function down(): void
    {
        Schema::table('shop_variants', function (Blueprint $table) {
            $table->dropColumn([
                'is_unlimited_data', 'network_speeds', 'networks', 'hotspot_supported',
                'voice_supported', 'sms_supported', 'topup_supported', 'activation_policy',
                'installation_deadline_days', 'fair_usage_note',
            ]);
        });
    }
};
