<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A deliberately separate table from `disputes` rather than a nullable
 * user_id retrofit: disputes.user_id/dispute_messages.user_id are
 * NOT NULL with cascadeOnDelete and the whole authenticated /support thread
 * UI assumes a real user on every message. Retrofitting that live, working
 * system risked regressing real customer support flows. Staff can convert a
 * guest ticket into a real Dispute (see convertToDispute()) once the person
 * has or creates an account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // PB-GST-XXXX
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->string('category')->default('general'); // deposit | funding | agent | general
            $table->text('description');
            $table->string('attachment_path')->nullable(); // PRIVATE disk
            $table->string('status')->default('open'); // open | in_progress | resolved | closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('converted_to_dispute_id')->nullable()->constrained('disputes')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_support_tickets');
    }
};
