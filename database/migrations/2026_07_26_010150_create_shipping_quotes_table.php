<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 14, 2);
            $table->string('currency', 3)->default('XAF');
            $table->unsignedInteger('eta_days');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending'); // pending|accepted|rejected|withdrawn
            $table->timestamps();

            $table->unique(['shipping_request_id', 'agent_id']);
        });

        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->foreign('accepted_quote_id')->references('id')->on('shipping_quotes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_requests', function (Blueprint $table) {
            $table->dropForeign(['accepted_quote_id']);
        });
        Schema::dropIfExists('shipping_quotes');
    }
};
