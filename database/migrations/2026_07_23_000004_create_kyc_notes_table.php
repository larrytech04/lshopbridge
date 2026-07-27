<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Private reviewer notes. Never surfaced to the customer, never included in
        // customer-facing exports or notifications.
        Schema::create('kyc_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kyc_verification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('kyc_verification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_notes');
    }
};
