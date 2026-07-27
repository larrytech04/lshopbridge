<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable event log powering the wallet-history timeline. Never edited or
        // deleted — every status change or note appends a new row.
        Schema::create('beneficiary_account_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event'); // submitted | updated | review_started | info_requested | resubmitted | approved | rejected | suspended | restored | note_added | archived
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['beneficiary_account_id', 'created_at'], 'bae_account_id_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_account_events');
    }
};
