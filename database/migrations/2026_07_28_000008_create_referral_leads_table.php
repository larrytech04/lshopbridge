<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Low-commitment "interested in becoming an agent" lead capture — distinct
 * from the full /register/agent account-creation flow. Staff follow up and
 * guide qualified leads into that real registration flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // PB-REF-XXXX
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->default('new'); // new | contacted | converted | declined
            $table->foreignId('contacted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contacted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_leads');
    }
};
