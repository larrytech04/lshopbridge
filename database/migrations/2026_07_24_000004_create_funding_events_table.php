<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable per-request timeline. Never edited or deleted.
        Schema::create('funding_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event'); // created | payment_successful | submitted | processing | completed | manual_review | failed | cancelled | refund_completed | requeried | escalated | flagged_for_investigation | note_added | assigned | reconciled
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['funding_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funding_events');
    }
};
