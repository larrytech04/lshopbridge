<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real run-history for scheduled commands, populated by ->before()/->after()
 * hooks in routes/console.php — not a fabricated "worker is running" signal.
 * A command's "last run/success/failure" only exists here if the scheduler
 * actually fired it; there is no independent proof a system cron invokes
 * `schedule:run` in whatever environment this deploys to (see the ops
 * checklist), so an empty table is a real, honest state, not a bug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_runs', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->boolean('successful')->nullable();
            $table->text('output')->nullable();
            $table->timestamps();

            $table->index(['command', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_runs');
    }
};
