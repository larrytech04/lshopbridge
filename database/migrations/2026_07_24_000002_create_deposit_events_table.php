<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable per-deposit timeline. Never edited or deleted — every action
        // appends a new row. Complements audit_logs (which is action-centric, not
        // deposit-centric) with a structured from/to status trail for the drawer.
        Schema::create('deposit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event'); // created | requeried | confirmed | rejected | info_requested | failed | refund_initiated | refund_completed | reversed | escalated | flagged_for_investigation | note_added | reconciled | assigned
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['deposit_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_events');
    }
};
