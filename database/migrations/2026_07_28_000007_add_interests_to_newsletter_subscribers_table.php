<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            // Which of the LshopBridge Brief topic checkboxes the subscriber
            // selected (see NewsletterController::INTERESTS). Null/empty means
            // no preference was expressed — treated as "all topics".
            $table->json('interests')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn('interests');
        });
    }
};
