<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Device eSIM-compatibility reference data (section 36 of the spec). Seeded
 * with publicly documented, factual compatibility (e.g. "iPhone XS and
 * later support eSIM") — never guessed per-model. `source` distinguishes a
 * reviewed/verified entry from an unreviewed one so crowdsourced feedback
 * (not built in this phase) could never silently outrank verified data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_devices', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->json('model_variants')->nullable(); // e.g. region-specific model numbers
            $table->boolean('esim_supported');
            $table->text('regional_restriction')->nullable();
            $table->text('carrier_lock_note')->nullable();
            $table->string('min_os_version')->nullable();
            $table->boolean('dual_sim_support')->nullable();
            $table->unsignedTinyInteger('max_active_esims')->nullable();
            $table->string('installation_method')->nullable(); // qr | manual | app | qr_manual
            $table->date('verified_date')->nullable();
            $table->string('source')->default('manual'); // manual | provider | community
            $table->string('status')->default('active'); // active | disabled
            $table->timestamps();

            $table->unique(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_devices');
    }
};
