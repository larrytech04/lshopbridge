<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A row per usage poll from a real provider (App\Services\Esim\EsimUsageService,
 * once a connector supports getUsage()). Until then, no rows are ever
 * created — the UI must show "Usage information is unavailable from this
 * provider" rather than estimate, per the eSIM spec's own instruction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_usage_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esim_provisioning_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('total_mb')->nullable();
            $table->unsignedBigInteger('used_mb')->nullable();
            $table->unsignedBigInteger('remaining_mb')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['esim_provisioning_id', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_usage_snapshots');
    }
};
